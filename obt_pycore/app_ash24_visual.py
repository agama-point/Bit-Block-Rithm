#!/usr/bin/env python
# -*- coding: utf-8 -*-

import sys
from PyQt6.QtWidgets import (QApplication, QMainWindow, QWidget, QVBoxLayout, 
                             QHBoxLayout, QLineEdit, QCheckBox, QPushButton, 
                             QLabel, QTextEdit, QScrollArea, QFrame)
from PyQt6.QtGui import QPainter, QColor, QFont, QPen
from PyQt6.QtCore import Qt, QTimer

# --- IMPORT EXTERNÍHO UI MODULU ---
import qt_ui

# --- Jádro algoritmu ASH24 (beze změn) ---
def rol8(x, r):
    return ((x << r) | (x >> (8 - r))) & 0xFF

def get_ash24_trace(input_str, use_iv, use_padding):
    data = list(input_str.encode('utf-8'))
    original_len = len(data)
    if use_padding:
        data.append(0x80)
        while (len(data) + 2) % 2 != 0: data.append(0x00)
        data.append((original_len >> 8) & 0xFF); data.append(original_len & 0xFF)
    else:
        if len(data) % 2 != 0: data.append(0x00)
    iv8 = [0x6a, 0xbb, 0x3c, 0xa5, 0x51, 0x9b, 0x05, 0x1f] if use_iv else [0]*8
    a, b, c = iv8[0], iv8[1], iv8[2]
    history = []
    for block_idx in range(0, len(data), 2):
        m0, m1 = data[block_idx], data[block_idx + 1]
        a ^= m0; b ^= m1; c ^= (m0 + m1) & 0xFF
        history.append({'a': a, 'b': b, 'c': c, 'label': "INIT"})
        for i in range(16):
            a ^= iv8[(i + block_idx) % 8]; b ^= rol8(c, 2); c ^= rol8(a, 3)
            a = (a + c) & 0xFF; a ^= b; b ^= c; c ^= a
            a, b, c = b, c, a
            history.append({'a': a, 'b': b, 'c': c, 'label': f"R{i+1}"})
    return f"{((a << 16) | (b << 8) | c) & 0xFFFFFF:06x}", history

# --- Vizualizační Widget ---
class HashCanvas(QWidget):
    def __init__(self):
        super().__init__()
        self.history = []
        self.current_shown = 0
        self.is_dark = True
        self.setMinimumWidth(300)

    def paintEvent(self, event):
        painter = QPainter(self)
        
        # Barvy z centralizovaného qt_ui
        bg_color = qt_ui.get_canvas_bg_color(self.is_dark)
        bit_on = qt_ui.get_fg_color(self.is_dark)
        bit_off = QColor(30, 30, 30) if self.is_dark else QColor(180, 180, 180)
        text_color = qt_ui.get_text_color(self.is_dark)
        
        painter.fillRect(self.rect(), bg_color)
        if not self.history:
            return
            
        bit_size, bit_gap, reg_gap, row_gap, start_x, start_y = 6, 1, 10, 4, 50, 40
        painter.setFont(QFont("Monospace", 8))
        painter.setPen(text_color)
        
        if self.current_shown > 0:
            painter.drawText(start_x, 25, "REG A")
            painter.drawText(start_x + 60, 25, "REG B")
            painter.drawText(start_x + 120, 25, "REG C")
            
        for i in range(min(self.current_shown, len(self.history))):
            state = self.history[i]
            y = start_y + i * (bit_size + row_gap)
            painter.setPen(bit_on if state['label'] == "INIT" else text_color)
            painter.drawText(5, y + bit_size, state['label'])
            regs = [state['a'], state['b'], state['c']]
            for r_idx, val in enumerate(regs):
                x_off = start_x + r_idx * (8 * (bit_size + bit_gap) + reg_gap)
                for b_idx in range(8):
                    bit = (val >> (7 - b_idx)) & 1
                    painter.setBrush(bit_on if bit else bit_off)
                    painter.setPen(Qt.PenStyle.NoPen)
                    painter.drawRect(x_off + b_idx * (bit_size + bit_gap), y, bit_size, bit_size)

# --- Hlavní okno ---
class MainWindow(QMainWindow):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("ASH24 | Bitwise Visualizer")
        self.is_dark = True
        self.history = []
        self.final_hex = "000000"
        self.setup_ui()
        self.timer = QTimer()
        self.timer.timeout.connect(self.animate_step)
        
    def setup_ui(self):
        main_widget = QWidget()
        self.setCentralWidget(main_widget)
        self.main_layout = QVBoxLayout(main_widget)

        # 1. Panel ovládání (horní)
        ui_panel = QVBoxLayout()
        title = QLabel("ASH24 Visual")
        ui_panel.addWidget(title, alignment=Qt.AlignmentFlag.AlignCenter)
        self.title_label = title  # Uložení reference pro stylování

        self.input_field = QLineEdit("@")
        ui_panel.addWidget(self.input_field)

        cfg_layout = QHBoxLayout()
        self.cb_iv = QCheckBox("Use IV")
        self.cb_iv.setChecked(True)
        self.cb_pad = QCheckBox("Use Padding")
        self.cb_pad.setChecked(True)
        cfg_layout.addWidget(self.cb_iv)
        cfg_layout.addWidget(self.cb_pad)
        ui_panel.addLayout(cfg_layout)

        self.btn_run = QPushButton("RUN HASH")
        self.btn_run.clicked.connect(self.start_hashing)
        ui_panel.addWidget(self.btn_run)
        self.main_layout.addLayout(ui_panel)

        # 2. Střední část (kód + plátno)
        content_layout = QHBoxLayout()
        self.code_view = QTextEdit()
        self.code_view.setReadOnly(True)
        self.code_view.setPlainText("// ASH24 Core Logic\n// Linear & Non-linear Mixing\n\nfor (i=0; i<16; i++) {\n  B ^= rol(C, 2);\n  C ^= rol(A, 3);\n  A = (A + C) & 0xFF;\n  ...\n}")
        self.code_view.setObjectName("code_area")  # Pro identifikaci
        content_layout.addWidget(self.code_view, 1)

        self.scroll = QScrollArea()
        self.canvas = HashCanvas()
        self.scroll.setWidget(self.canvas)
        self.scroll.setWidgetResizable(True)
        self.scroll.setFrameShape(QFrame.Shape.NoFrame)
        content_layout.addWidget(self.scroll, 2)
        self.main_layout.addLayout(content_layout)

        # 3. Výsledek (velký uprostřed)
        self.result_label = QLabel("RESULT: 000000")
        self.main_layout.addWidget(self.result_label, alignment=Qt.AlignmentFlag.AlignCenter)

        # 4. Stavový řádek (spodní lišta)
        status_bar = QHBoxLayout()
        status_bar.setContentsMargins(
            qt_ui.DIMENSIONS["status_bar_margin"],
            qt_ui.DIMENSIONS["status_bar_margin"],
            qt_ui.DIMENSIONS["status_bar_margin"],
            qt_ui.DIMENSIONS["status_bar_margin"]
        )
        
        self.status_label = QLabel("Status: Ready")
        self.status_label.setFont(QFont("Arial", 8))
        
        self.btn_theme = QPushButton("Theme")
        self.btn_theme.setObjectName("theme_button")
        self.btn_theme.setFixedWidth(qt_ui.DIMENSIONS["theme_button_width"])
        self.btn_theme.clicked.connect(self.toggle_theme)
        
        status_bar.addWidget(self.status_label)
        status_bar.addStretch()
        status_bar.addWidget(self.btn_theme)
        
        self.main_layout.addLayout(status_bar)

        self.apply_theme()

    def apply_theme(self):
        # Použití centralizovaných stylů z qt_ui
        qt_ui.apply_styles_to_widgets(self, self.is_dark)
        
        # Specifické styly
        self.title_label.setStyleSheet(qt_ui.get_header_style(self.is_dark, size=20))
        self.code_view.setStyleSheet(qt_ui.get_text_edit_style(self.is_dark, variant="code"))
        self.status_label.setStyleSheet(qt_ui.get_status_label_style(self.is_dark))
        
        # Styl pro result label s akcentní barvou
        res_color = qt_ui.COLORS['dark_accent'] if self.is_dark else qt_ui.COLORS['light_accent']
        self.result_label.setStyleSheet(f"color: {res_color}; font-size: 24px; font-weight: bold; padding: 10px;")

        self.canvas.is_dark = self.is_dark
        self.canvas.update()

    def toggle_theme(self):
        self.is_dark = not self.is_dark
        self.apply_theme()

    def start_hashing(self):
        text = self.input_field.text()
        self.status_label.setText(f"Status: Hashing '{text}'...")
        hex_res, hist = get_ash24_trace(text, self.cb_iv.isChecked(), self.cb_pad.isChecked())
        self.final_hex = hex_res.upper()
        self.history = hist
        self.canvas.history = hist
        self.canvas.current_shown = 0
        self.canvas.setFixedHeight(len(hist) * 10 + 100)
        self.result_label.setText("MIXING...")
        self.timer.start(30)

    def animate_step(self):
        if self.canvas.current_shown < len(self.history):
            self.canvas.current_shown += 1
            self.canvas.update()
        else:
            self.timer.stop()
            self.result_label.setText(f"RESULT: {self.final_hex}")
            self.status_label.setText("Status: Done")

if __name__ == "__main__":
    app = QApplication(sys.argv)
    window = MainWindow()
    window.resize(900, 750)
    window.show()
    sys.exit(app.exec())
