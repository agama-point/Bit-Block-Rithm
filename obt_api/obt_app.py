#!/usr/bin/env python3
# obt_app.py
"""
OBT UART/API Application
Communication with device via UART + blockchain API queries
"""

import sys
import json
import time
from datetime import datetime
from typing import Optional

from PyQt6.QtCore import QObject, QThread, pyqtSignal, pyqtSlot
from PyQt6.QtWidgets import QApplication

import serial
import serial.tools.list_ports
import requests

from obt_ui import MainWindow


class WorkerThread(QObject):
    """Worker for asynchronous operations – UART communication and API queries"""
    
    # Signals for UI
    log_signal = pyqtSignal(str)              # HTML messages to log
    status_signal = pyqtSignal(str)           # Status text
    ports_found_signal = pyqtSignal(list)     # List of found ports
    connected_signal = pyqtSignal(bool)       # Connection state
    address_received_signal = pyqtSignal(str) # Public key/address from device
    balance_received_signal = pyqtSignal(dict) # Balance data from API
    
    def __init__(self):
        super().__init__()
        self._serial: Optional[serial.Serial] = None
        self._device_address: str = ""
        self._debug_mode: bool = False
    
    # ------------------------------------------------------------------ #
    #  UART operations                                                     #
    # ------------------------------------------------------------------ #
    
    @pyqtSlot()
    def scan_ports(self):
        """Scan available UART ports"""
        self._log("🔍 Scanning UART ports...", color="#ffb300")
        self.status_signal.emit("Scanning …")
        
        try:
            ports = serial.tools.list_ports.comports()
            if not ports:
                self._log("⚠️ No serial ports found", color="#f44336")
                self.status_signal.emit("Idle")
                self.ports_found_signal.emit([])
                return
            
            port_list = []
            for port in ports:
                info = f"{port.device} — {port.description}"
                port_list.append((port.device, info))
                if self._debug_mode:
                    self._log(f"  • {info}", color="#888")
            
            self._log(f"✓ Found {len(port_list)} port(s)", color="#4caf50")
            self.status_signal.emit("Idle")
            self.ports_found_signal.emit(port_list)
            
        except Exception as e:
            self._log(f"❌ Scan error: {e}", color="#f44336")
            self.status_signal.emit("Error")
            self.ports_found_signal.emit([])
    
    @pyqtSlot(str)
    def connect_port(self, port_name: str):
        """Connect to selected port"""
        self._log(f"🔌 Connecting to {port_name}...", color="#ffb300")
        self.status_signal.emit("Connecting …")
        
        try:
            self._serial = serial.Serial(port_name, 115200, timeout=2)
            self._log(f"✓ Connected to {port_name}", color="#4caf50")
            
            # ESP32 may reset when opening port
            self._log("⏳ Waiting for device initialization (2s)...", color="#888")
            time.sleep(2)
            self._serial.flushInput()
            
            self.status_signal.emit("Connected")
            self.connected_signal.emit(True)
            
            # Automatically request public key
            self._request_address()
            
        except Exception as e:
            self._log(f"❌ Connection error: {e}", color="#f44336")
            self.status_signal.emit("Error")
            self.connected_signal.emit(False)
            if self._serial and self._serial.is_open:
                self._serial.close()
                self._serial = None
    
    @pyqtSlot()
    def disconnect_port(self):
        """Disconnect UART port"""
        if self._serial and self._serial.is_open:
            self._serial.close()
            self._log("🔌 Port closed", color="#888")
        self._serial = None
        self._device_address = ""
        self.status_signal.emit("Idle")
        self.connected_signal.emit(False)
    
    def _request_address(self):
        """Request public key from device"""
        if not self._serial or not self._serial.is_open:
            return
        
        try:
            command = {"get": "addr"}
            json_command = json.dumps(command) + "\n"
            
            self._log("📤 Requesting public key...", color="#2196f3")
            if self._debug_mode:
                self._log(f"  TX: {json_command.strip()}", color="#666")
            
            self._serial.write(json_command.encode('utf-8'))
            
            # Read response
            found_address = False
            start_time = time.time()
            
            while (time.time() - start_time) < 5:
                line = self._serial.readline().decode('utf-8', errors='ignore').strip()
                if line:
                    if self._debug_mode:
                        self._log(f"  RX: {line}", color="#666")
                    
                    # Ignore debug outputs
                    if line.startswith("::") or line == "READY":
                        continue
                    
                    # Assume the rest is the address
                    self._device_address = line
                    self._log(f"🔑 Public key: <b>{line}</b>", color="#4caf50")
                    self.address_received_signal.emit(line)
                    found_address = True
                    break
            
            if not found_address:
                self._log("⚠️ Device did not respond in time", color="#f44336")
                
        except Exception as e:
            self._log(f"❌ Error reading address: {e}", color="#f44336")
    
    # ------------------------------------------------------------------ #
    #  API operations                                                      #
    # ------------------------------------------------------------------ #
    
    @pyqtSlot(str)
    def get_balance(self, address: str):
        """Get balance and UTXOs from API"""
        if not address:
            self._log("⚠️ No address provided", color="#f44336")
            return
        
        base_url = "https://www.agamapoint.com/bbr/index.php?route=get_balance/"
        url = f"{base_url}{address}"
        
        self._log(f"🌐 Querying API: {address}", color="#2196f3")
        if self._debug_mode:
            self._log(f"  URL: {url}", color="#666")
        
        try:
            response = requests.get(url, timeout=10)
            response.raise_for_status()
            data = response.json()
            
            if data.get("status") == "ok":
                balance = data.get("balance", 0)
                utxo_count = data.get("utxo_count", 0)
                
                self._log(f"✓ Balance: <b>{balance}</b> units | UTXOs: {utxo_count}", 
                         color="#4caf50")
                
                if self._debug_mode and data.get("unspent_outputs"):
                    for utxo in data["unspent_outputs"]:
                        # Fix: Convert txid to string to handle integer values
                        txid = str(utxo.get('txid', ''))
                        value = utxo.get('value', 0)
                        self._log(f"  • TXID: {txid[:16]}... | {value} units", 
                                 color="#888")
                
                self.balance_received_signal.emit(data)
            else:
                self._log(f"❌ API error: {data.get('status')}", color="#f44336")
                
        except requests.exceptions.Timeout:
            self._log("❌ Timeout - API did not respond in time", color="#f44336")
        except requests.exceptions.RequestException as e:
            self._log(f"❌ API request error: {e}", color="#f44336")
        except json.JSONDecodeError:
            self._log("❌ API returned invalid JSON", color="#f44336")
    
    @pyqtSlot(str, str, list)
    def send_transaction(self, to_address: str, from_address: str, selected_utxos: list):
        """Placeholder for sending transaction (dummy for now)"""
        self._log(f"📨 DUMMY: Sending to {to_address}", color="#ff9800")
        self._log(f"  From address: {from_address}", color="#888")
        self._log(f"  Selected UTXOs: {len(selected_utxos)}", color="#888")
        self._log("  ⚠️ Function not implemented yet", color="#666")
    
    # ------------------------------------------------------------------ #
    #  Utility                                                             #
    # ------------------------------------------------------------------ #
    
    @pyqtSlot(bool)
    def set_debug_mode(self, enabled: bool):
        """Enable/disable debug mode"""
        self._debug_mode = enabled
        mode = "ENABLED" if enabled else "DISABLED"
        self._log(f"🐛 Debug mode {mode}", color="#888")
    
    def _log(self, message: str, color: str = "#e0e0e0"):
        """Internal helper for logging with timestamp"""
        timestamp = datetime.now().strftime("%H:%M:%S")
        html = f"<span style='color:#666;'>[{timestamp}]</span> " \
               f"<span style='color:{color};'>{message}</span>"
        self.log_signal.emit(html)


def main():
    app = QApplication(sys.argv)
    
    # Worker runs in its own thread
    worker = WorkerThread()
    thread = QThread()
    worker.moveToThread(thread)
    thread.start()
    
    # UI window
    window = MainWindow(worker)
    window.show()
    
    try:
        exit_code = app.exec()
    finally:
        thread.quit()
        thread.wait()
    
    sys.exit(exit_code)


if __name__ == "__main__":
    main()
