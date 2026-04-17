#!/usr/bin/env python3
# obt_app.py
"""
OBT UART/API Application
Komunikace se zařízením přes UART + blockchain API dotazy
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
    """Worker pro asynchronní operace – UART komunikace a API dotazy"""
    
    # Signály pro UI
    log_signal = pyqtSignal(str)              # HTML zprávy do logu
    status_signal = pyqtSignal(str)           # Status text
    ports_found_signal = pyqtSignal(list)     # Seznam nalezených portů
    connected_signal = pyqtSignal(bool)       # Stav připojení
    address_received_signal = pyqtSignal(str) # Veřejný klíč/adresa ze zařízení
    balance_received_signal = pyqtSignal(dict) # Data o balance z API
    
    def __init__(self):
        super().__init__()
        self._serial: Optional[serial.Serial] = None
        self._device_address: str = ""
        self._debug_mode: bool = False
    
    # ------------------------------------------------------------------ #
    #  UART operace                                                        #
    # ------------------------------------------------------------------ #
    
    @pyqtSlot()
    def scan_ports(self):
        """Naskenuje dostupné UART porty"""
        self._log("🔍 Skenování UART portů...", color="#ffb300")
        self.status_signal.emit("Scanning …")
        
        try:
            ports = serial.tools.list_ports.comports()
            if not ports:
                self._log("⚠️ Nebyly nalezeny žádné sériové porty", color="#f44336")
                self.status_signal.emit("Idle")
                self.ports_found_signal.emit([])
                return
            
            port_list = []
            for port in ports:
                info = f"{port.device} — {port.description}"
                port_list.append((port.device, info))
                if self._debug_mode:
                    self._log(f"  • {info}", color="#888")
            
            self._log(f"✓ Nalezeno portů: {len(port_list)}", color="#4caf50")
            self.status_signal.emit("Idle")
            self.ports_found_signal.emit(port_list)
            
        except Exception as e:
            self._log(f"❌ Chyba při skenování: {e}", color="#f44336")
            self.status_signal.emit("Error")
            self.ports_found_signal.emit([])
    
    @pyqtSlot(str)
    def connect_port(self, port_name: str):
        """Připojí se k vybranému portu"""
        self._log(f"🔌 Připojování k {port_name}...", color="#ffb300")
        self.status_signal.emit("Connecting …")
        
        try:
            self._serial = serial.Serial(port_name, 115200, timeout=2)
            self._log(f"✓ Připojeno k {port_name}", color="#4caf50")
            
            # ESP32 se může resetovat při otevření portu
            self._log("⏳ Čekám na inicializaci zařízení (2s)...", color="#888")
            time.sleep(2)
            self._serial.flushInput()
            
            self.status_signal.emit("Connected")
            self.connected_signal.emit(True)
            
            # Automaticky získáme veřejný klíč
            self._request_address()
            
        except Exception as e:
            self._log(f"❌ Chyba připojení: {e}", color="#f44336")
            self.status_signal.emit("Error")
            self.connected_signal.emit(False)
            if self._serial and self._serial.is_open:
                self._serial.close()
                self._serial = None
    
    @pyqtSlot()
    def disconnect_port(self):
        """Odpojí UART port"""
        if self._serial and self._serial.is_open:
            self._serial.close()
            self._log("🔌 Port uzavřen", color="#888")
        self._serial = None
        self._device_address = ""
        self.status_signal.emit("Idle")
        self.connected_signal.emit(False)
    
    def _request_address(self):
        """Požádá zařízení o veřejný klíč"""
        if not self._serial or not self._serial.is_open:
            return
        
        try:
            command = {"get": "addr"}
            json_command = json.dumps(command) + "\n"
            
            self._log("📤 Dotaz na veřejný klíč...", color="#2196f3")
            if self._debug_mode:
                self._log(f"  TX: {json_command.strip()}", color="#666")
            
            self._serial.write(json_command.encode('utf-8'))
            
            # Čtení odpovědi
            found_address = False
            start_time = time.time()
            
            while (time.time() - start_time) < 5:
                line = self._serial.readline().decode('utf-8', errors='ignore').strip()
                if line:
                    if self._debug_mode:
                        self._log(f"  RX: {line}", color="#666")
                    
                    # Ignorujeme debug výpisy
                    if line.startswith("::") or line == "READY":
                        continue
                    
                    # Předpokládáme, že zbytek je adresa
                    self._device_address = line
                    self._log(f"🔑 Veřejný klíč: <b>{line}</b>", color="#4caf50")
                    self.address_received_signal.emit(line)
                    found_address = True
                    break
            
            if not found_address:
                self._log("⚠️ Zařízení neodpovědělo včas", color="#f44336")
                
        except Exception as e:
            self._log(f"❌ Chyba při čtení adresy: {e}", color="#f44336")
    
    # ------------------------------------------------------------------ #
    #  API operace                                                         #
    # ------------------------------------------------------------------ #
    
    @pyqtSlot(str)
    def get_balance(self, address: str):
        """Získá balance a UTXO z API"""
        if not address:
            self._log("⚠️ Není zadána adresa", color="#f44336")
            return
        
        base_url = "https://www.agamapoint.com/bbr/index.php?route=get_balance/"
        url = f"{base_url}{address}"
        
        self._log(f"🌐 Dotaz na API: {address}", color="#2196f3")
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
                        self._log(f"  • TXID: {utxo['txid'][:16]}... | {utxo['value']} units", 
                                 color="#888")
                
                self.balance_received_signal.emit(data)
            else:
                self._log(f"❌ API chyba: {data.get('status')}", color="#f44336")
                
        except requests.exceptions.Timeout:
            self._log("❌ Timeout - API neodpovědělo včas", color="#f44336")
        except requests.exceptions.RequestException as e:
            self._log(f"❌ Chyba API požadavku: {e}", color="#f44336")
        except json.JSONDecodeError:
            self._log("❌ API vrátilo neplatný JSON", color="#f44336")
    
    @pyqtSlot(str, str, list)
    def send_transaction(self, to_address: str, from_address: str, selected_utxos: list):
        """Placeholder pro odeslání transakce (zatím dummy)"""
        self._log(f"📨 DUMMY: Odesílání na {to_address}", color="#ff9800")
        self._log(f"  Z adresy: {from_address}", color="#888")
        self._log(f"  Vybraných UTXOs: {len(selected_utxos)}", color="#888")
        self._log("  ⚠️ Funkce zatím není implementována", color="#666")
    
    # ------------------------------------------------------------------ #
    #  Utility                                                             #
    # ------------------------------------------------------------------ #
    
    @pyqtSlot(bool)
    def set_debug_mode(self, enabled: bool):
        """Zapne/vypne debug režim"""
        self._debug_mode = enabled
        mode = "ZAPNUT" if enabled else "VYPNUT"
        self._log(f"🐛 Debug režim {mode}", color="#888")
    
    def _log(self, message: str, color: str = "#e0e0e0"):
        """Interní helper pro logování s timestampem"""
        timestamp = datetime.now().strftime("%H:%M:%S")
        html = f"<span style='color:#666;'>[{timestamp}]</span> " \
               f"<span style='color:{color};'>{message}</span>"
        self.log_signal.emit(html)


def main():
    app = QApplication(sys.argv)
    
    # Worker běží ve vlastním vlákně
    worker = WorkerThread()
    thread = QThread()
    worker.moveToThread(thread)
    thread.start()
    
    # UI okno
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
