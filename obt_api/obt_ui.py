# obt_ui.py
# UI vrstva pro OBT aplikaci

from PyQt6.QtWidgets import (
    QWidget, QVBoxLayout, QHBoxLayout, QSplitter,
    QPushButton, QCheckBox, QLineEdit, QLabel,
    QTextBrowser, QGroupBox, QComboBox, QScrollArea,
)
from PyQt6.QtCore import Qt, pyqtSlot
from PyQt6.QtGui import QFont


class MainWindow(QWidget):
    def __init__(self, worker):
        super().__init__()
        self._worker = worker
        self.setWindowTitle("OBT | UART Blockchain Tool")
        self.resize(1000, 700)
        self.setMinimumWidth(800)

        # Připojení signálů z workera
        self._worker.log_signal.connect(self._append_log)
        self._worker.status_signal.connect(self._set_status)
        self._worker.ports_found_signal.connect(self._update_port_list)
        self._worker.connected_signal.connect(self._on_connection_changed)
        self._worker.address_received_signal.connect(self._set_device_address)
        self._worker.balance_received_signal.connect(self._display_balance)

        self._utxo_checkboxes = []  # Uložení checkboxů pro UTXOs

        self._build_ui()
        self.apply_dark_theme()

    # ------------------------------------------------------------------ #
    #  Layout                                                              #
    # ------------------------------------------------------------------ #
    def _build_ui(self):
        root = QVBoxLayout(self)
        root.setContentsMargins(10, 10, 10, 10)
        root.setSpacing(8)

        splitter = QSplitter(Qt.Orientation.Horizontal)
        splitter.setHandleWidth(6)

        left_panel = self._build_left_panel()
        left_panel.setFixedWidth(380)
        right_panel = self._build_right_panel()

        splitter.addWidget(left_panel)
        splitter.addWidget(right_panel)
        splitter.setStretchFactor(0, 0)
        splitter.setStretchFactor(1, 1)

        root.addWidget(splitter, stretch=1)

        # Bottom bar
        bottom = QHBoxLayout()
        clear_btn = QPushButton("Clear log")
        clear_btn.clicked.connect(self.log_box.clear)
        clear_btn.setFixedWidth(100)

        self.theme_toggle = QCheckBox("Dark mode")
        self.theme_toggle.setChecked(True)
        self.theme_toggle.stateChanged.connect(self._toggle_theme)

        bottom.addWidget(clear_btn)
        bottom.addStretch()
        bottom.addWidget(self.theme_toggle)
        root.addLayout(bottom)

    # ------------------------------------------------------------------ #
    #  Left panel                                                          #
    # ------------------------------------------------------------------ #
    def _build_left_panel(self) -> QWidget:
        panel = QWidget()
        layout = QVBoxLayout(panel)
        layout.setContentsMargins(0, 0, 4, 0)
        layout.setSpacing(10)

        layout.addWidget(self._group_uart())
        layout.addWidget(self._group_connection())
        layout.addWidget(self._group_balance())
        layout.addWidget(self._group_payment())
        layout.addStretch()
        layout.addWidget(self._group_debug())

        return panel

    def _group_uart(self) -> QGroupBox:
        """UART – skenování a výběr portu"""
        grp = QGroupBox("UART")
        lay = QVBoxLayout(grp)
        lay.setSpacing(6)

        # Scan button + status
        row = QHBoxLayout()
        self.scan_btn = QPushButton("⟳  Scan ports")
        self.scan_btn.clicked.connect(self._worker.scan_ports)

        self.status_label = QLabel("● Idle")
        self.status_label.setAlignment(
            Qt.AlignmentFlag.AlignRight | Qt.AlignmentFlag.AlignVCenter
        )

        row.addWidget(self.scan_btn)
        row.addStretch()
        row.addWidget(self.status_label)
        lay.addLayout(row)

        # Port selector
        port_row = QHBoxLayout()
        port_lbl = QLabel("Port:")
        port_lbl.setFixedWidth(42)
        self.port_combo = QComboBox()
        self.port_combo.setFont(QFont("Monospace", 9))
        self.port_combo.setEnabled(False)
        port_row.addWidget(port_lbl)
        port_row.addWidget(self.port_combo, stretch=1)
        lay.addLayout(port_row)

        return grp

    def _group_connection(self) -> QGroupBox:
        """Connection – připojení a zobrazení adresy"""
        grp = QGroupBox("Connection")
        lay = QVBoxLayout(grp)
        lay.setSpacing(6)

        # Device address
        addr_row = QHBoxLayout()
        addr_lbl = QLabel("Address:")
        addr_lbl.setFixedWidth(60)
        self.addr_label = QLabel("—")
        self.addr_label.setFont(QFont("Monospace", 9))
        self.addr_label.setWordWrap(True)
        self.addr_label.setTextInteractionFlags(Qt.TextInteractionFlag.TextSelectableByMouse)
        addr_row.addWidget(addr_lbl)
        addr_row.addWidget(self.addr_label, stretch=1)
        lay.addLayout(addr_row)

        # Connect button
        self.connect_btn = QPushButton("Connect")
        self.connect_btn.clicked.connect(self._toggle_connection)
        self.connect_btn.setEnabled(False)
        lay.addWidget(self.connect_btn)

        return grp

    def _group_balance(self) -> QGroupBox:
        """Balance – tlačítko Get Balance a seznam UTXOs"""
        grp = QGroupBox("Balance")
        lay = QVBoxLayout(grp)
        lay.setSpacing(6)

        # Balance info
        bal_row = QHBoxLayout()
        bal_lbl = QLabel("Balance:")
        bal_lbl.setFixedWidth(60)
        self.balance_label = QLabel("—")
        self.balance_label.setFont(QFont("Monospace", 10, QFont.Weight.Bold))
        bal_row.addWidget(bal_lbl)
        bal_row.addWidget(self.balance_label, stretch=1)
        lay.addLayout(bal_row)

        # Get Balance button
        self.get_balance_btn = QPushButton("Get Balance")
        self.get_balance_btn.clicked.connect(self._on_get_balance)
        self.get_balance_btn.setEnabled(False)
        lay.addWidget(self.get_balance_btn)

        # UTXOs label
        utxo_lbl = QLabel("UTXOs (select to spend):")
        utxo_lbl.setStyleSheet("color: #888; font-size: 10px;")
        lay.addWidget(utxo_lbl)

        # Scrollable area for UTXOs
        scroll = QScrollArea()
        scroll.setWidgetResizable(True)
        scroll.setMaximumHeight(150)
        scroll.setStyleSheet("QScrollArea { border: 1px solid #555; border-radius: 4px; }")
        
        self.utxo_container = QWidget()
        self.utxo_layout = QVBoxLayout(self.utxo_container)
        self.utxo_layout.setContentsMargins(4, 4, 4, 4)
        self.utxo_layout.setSpacing(2)
        self.utxo_layout.addStretch()
        
        scroll.setWidget(self.utxo_container)
        lay.addWidget(scroll)

        return grp

    def _group_payment(self) -> QGroupBox:
        """Payment – odeslání transakce (dummy)"""
        grp = QGroupBox("Payment")
        lay = QVBoxLayout(grp)
        lay.setSpacing(6)

        # To address
        to_row = QHBoxLayout()
        to_lbl = QLabel("To:")
        to_lbl.setFixedWidth(60)
        self.to_input = QLineEdit()
        self.to_input.setPlaceholderText("Recipient address…")
        self.to_input.setFont(QFont("Monospace", 9))
        self.to_input.setEnabled(False)
        to_row.addWidget(to_lbl)
        to_row.addWidget(self.to_input, stretch=1)
        lay.addLayout(to_row)

        # Send button
        self.send_btn = QPushButton("Send  ➤")
        self.send_btn.setEnabled(False)
        self.send_btn.clicked.connect(self._on_send_payment)
        lay.addWidget(self.send_btn)

        # Dummy notice
        notice = QLabel("⚠️ Dummy – not implemented yet")
        notice.setStyleSheet("color: #ff9800; font-style: italic; font-size: 10px;")
        notice.setAlignment(Qt.AlignmentFlag.AlignCenter)
        lay.addWidget(notice)

        return grp

    def _group_debug(self) -> QGroupBox:
        """Debug toggle"""
        grp = QGroupBox("Debug")
        lay = QVBoxLayout(grp)

        self.debug_checkbox = QCheckBox("Verbose DEBUG output")
        self.debug_checkbox.setChecked(False)
        self.debug_checkbox.setToolTip("Show detailed UART and API diagnostics")
        self.debug_checkbox.stateChanged.connect(
            lambda state: self._worker.set_debug_mode(state == Qt.CheckState.Checked.value)
        )
        lay.addWidget(self.debug_checkbox)

        return grp

    # ------------------------------------------------------------------ #
    #  Right panel – log                                                   #
    # ------------------------------------------------------------------ #
    def _build_right_panel(self) -> QWidget:
        panel = QWidget()
        layout = QVBoxLayout(panel)
        layout.setContentsMargins(4, 0, 0, 0)
        layout.setSpacing(4)

        log_label = QLabel("Verbose Log")
        log_label.setFont(QFont("Monospace", 8))
        log_label.setStyleSheet("color: #888;")
        layout.addWidget(log_label)

        self.log_box = QTextBrowser()
        self.log_box.setReadOnly(True)
        self.log_box.setFont(QFont("Monospace", 9))
        self.log_box.setOpenExternalLinks(False)
        layout.addWidget(self.log_box, stretch=1)

        return panel

    # ------------------------------------------------------------------ #
    #  Slots / handlers                                                    #
    # ------------------------------------------------------------------ #
    @pyqtSlot(str)
    def _append_log(self, html_msg: str):
        """Přidá zprávu do logu"""
        self.log_box.append(html_msg)
        self.log_box.verticalScrollBar().setValue(
            self.log_box.verticalScrollBar().maximum()
        )

    @pyqtSlot(list)
    def _update_port_list(self, ports: list):
        """Aktualizuje seznam nalezených portů"""
        self.port_combo.clear()
        if ports:
            for device, description in ports:
                self.port_combo.addItem(description, device)
            self.port_combo.setEnabled(True)
            self.connect_btn.setEnabled(True)
        else:
            self.port_combo.setEnabled(False)
            self.connect_btn.setEnabled(False)

    @pyqtSlot(bool)
    def _on_connection_changed(self, connected: bool):
        """Reakce na změnu stavu připojení"""
        self.connect_btn.setText("Disconnect" if connected else "Connect")
        self.scan_btn.setEnabled(not connected)
        self.port_combo.setEnabled(not connected)
        
        # Enable balance & payment controls when connected
        self.get_balance_btn.setEnabled(connected)
        self.to_input.setEnabled(connected)
        self.send_btn.setEnabled(connected)

    @pyqtSlot(str)
    def _set_device_address(self, address: str):
        """Zobrazí přijatou adresu zařízení"""
        self.addr_label.setText(address)

    @pyqtSlot(dict)
    def _display_balance(self, data: dict):
        """Zobrazí balance a UTXOs"""
        balance = data.get("balance", 0)
        self.balance_label.setText(f"{balance} units")
        
        # Clear previous UTXOs
        for cb in self._utxo_checkboxes:
            cb.deleteLater()
        self._utxo_checkboxes.clear()
        
        # Add new UTXOs
        utxos = data.get("unspent_outputs", [])
        for utxo in utxos:
            txid = utxo.get("txid", "")
            value = utxo.get("value", 0)
            
            cb = QCheckBox(f"{txid[:20]}... | {value} units")
            cb.setFont(QFont("Monospace", 8))
            cb.setProperty("utxo_data", utxo)
            
            self._utxo_checkboxes.append(cb)
            self.utxo_layout.insertWidget(self.utxo_layout.count() - 1, cb)

    @pyqtSlot(str)
    def _set_status(self, text: str):
        """Nastaví status label"""
        colors = {
            "Connected":    "#4caf50",
            "Scanning …":   "#ffb300",
            "Connecting …": "#ffb300",
            "Idle":         "#9e9e9e",
        }
        color = colors.get(text, "#f44336")
        self.status_label.setText(f"● {text}")
        self.status_label.setStyleSheet(f"color: {color}; font-weight: bold;")

    def _toggle_connection(self):
        """Přepne stav připojení"""
        if self.connect_btn.text() == "Connect":
            selected_port = self.port_combo.currentData()
            if selected_port:
                self._worker.connect_port(selected_port)
        else:
            self._worker.disconnect_port()

    def _on_get_balance(self):
        """Zavolá worker pro získání balance"""
        address = self.addr_label.text()
        if address and address != "—":
            self._worker.get_balance(address)

    def _on_send_payment(self):
        """Dummy handler pro odeslání platby"""
        to_addr = self.to_input.text().strip()
        from_addr = self.addr_label.text()
        
        # Collect selected UTXOs
        selected = []
        for cb in self._utxo_checkboxes:
            if cb.isChecked():
                selected.append(cb.property("utxo_data"))
        
        if not to_addr:
            return
        
        self._worker.send_transaction(to_addr, from_addr, selected)

    # ------------------------------------------------------------------ #
    #  Themes                                                              #
    # ------------------------------------------------------------------ #
    def _toggle_theme(self):
        if self.theme_toggle.isChecked():
            self.apply_dark_theme()
        else:
            self.apply_light_theme()

    def apply_dark_theme(self):
        self.setStyleSheet("""
            QWidget { background: #2b2b2b; color: #e0e0e0; }
            QGroupBox {
                border: 1px solid #444;
                border-radius: 6px;
                margin-top: 6px;
                padding-top: 4px;
                font-weight: bold;
                color: #aaa;
            }
            QGroupBox::title { subcontrol-origin: margin; left: 8px; padding: 0 4px; }
            QTextBrowser, QLineEdit {
                background: #1e1e1e;
                border: 1px solid #555;
                border-radius: 4px;
                padding: 4px;
            }
            QComboBox {
                background: #1e1e1e;
                border: 1px solid #555;
                border-radius: 4px;
                padding: 3px 6px;
            }
            QComboBox::drop-down { border: none; }
            QComboBox QAbstractItemView {
                background: #1e1e1e;
                selection-background-color: #3c3c3c;
            }
            QPushButton {
                background: #3c3c3c;
                border: 1px solid #555;
                padding: 6px 10px;
                border-radius: 4px;
            }
            QPushButton:hover    { background: #505050; }
            QPushButton:pressed  { background: #2a2a2a; }
            QPushButton:disabled { color: #555; background: #2e2e2e; border-color: #3a3a3a; }
            QLineEdit:disabled   { color: #555; }
            QComboBox:disabled   { color: #555; }
            QCheckBox { spacing: 5px; }
            QSplitter::handle { background: #444; }
            QScrollArea { background: transparent; }
        """)

    def apply_light_theme(self):
        self.setStyleSheet("""
            QWidget { background: #f0f0f0; color: #222; }
            QGroupBox {
                border: 1px solid #ccc;
                border-radius: 6px;
                margin-top: 6px;
                padding-top: 4px;
                font-weight: bold;
                color: #555;
            }
            QGroupBox::title { subcontrol-origin: margin; left: 8px; padding: 0 4px; }
            QTextBrowser, QLineEdit {
                background: white;
                border: 1px solid #ccc;
                border-radius: 4px;
                padding: 4px;
            }
            QComboBox {
                background: white;
                border: 1px solid #ccc;
                border-radius: 4px;
                padding: 3px 6px;
            }
            QPushButton {
                background: #e1e1e1;
                border: 1px solid #bbb;
                padding: 6px 10px;
                border-radius: 4px;
            }
            QPushButton:hover    { background: #d0d0d0; }
            QPushButton:disabled { color: #aaa; }
            QSplitter::handle { background: #ccc; }
            QScrollArea { background: transparent; }
        """)
