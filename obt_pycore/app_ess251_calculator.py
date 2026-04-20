#!/usr/bin/env python
# -*- coding: utf-8 -*-

import sys
from PyQt6.QtWidgets import (QApplication, QMainWindow, QWidget, QVBoxLayout, 
                             QHBoxLayout, QLineEdit, QPushButton, QLabel, 
                             QTextEdit, QFrame, QGridLayout, QCheckBox)
from PyQt6.QtGui import QFont, QPainter, QColor, QPen
from PyQt6.QtCore import Qt

# --- IMPORT EXTERNÍHO UI MODULU ---
import qt_ui

# --- IMPORT KNIHOVNY ESS251 ---
try:
    from core.ess251 import (P_MOD, A_PARAM, G_POINT, ORDER_N, 
                             point_adding, scalar_mult)
except ImportError:
    # Fallback pokud knihovna chybí
    P_MOD, A_PARAM, ORDER_N = 251, 0, 252
    G_POINT = (1, 192)
    def point_adding(P1, P2, p, a): return None
    def scalar_mult(k, P, a, p, n): return None

class PointCanvas(QWidget):
    def __init__(self):
        super().__init__()
        self.setFixedSize(504, 504)
        self.points = []  # List of tuples: (x, y, color, label)
        self.draw_lines = True
        self.scale = 2
        self.marker_size = 6
        self.is_dark = True

    def add_point(self, pt, color, label=None):
        if pt and isinstance(pt, (tuple, list)):
            self.points.append((pt[0], pt[1], color, label))
            self.update()

    def clear_canvas(self):
        self.points = []
        self.update()

    def paintEvent(self, event):
        painter = QPainter(self)
        painter.setRenderHint(QPainter.RenderHint.Antialiasing, True)
        
        # Barvy z centralizovaného qt_ui
        bg_color = qt_ui.get_canvas_bg_color(self.is_dark)
        grid_color = qt_ui.get_grid_color(self.is_dark)
        text_color = qt_ui.get_text_color(self.is_dark)
        
        painter.fillRect(self.rect(), bg_color)
        
        # Mřížka
        painter.setPen(grid_color)
        for i in range(0, 252, 50):
            pos = i * self.scale
            painter.drawLine(pos, 0, pos, 504)
            painter.drawLine(0, pos, 504, pos)

        if not self.points:
            return

        def to_screen(x, y):
            return x * self.scale, 502 - (y * self.scale)

        # 1. Kreslení čar
        if self.draw_lines and len(self.points) > 1:
            line_color = QColor(0, 255, 65, 80) if self.is_dark else QColor(128, 0, 0, 80)
            painter.setPen(QPen(line_color, 1))
            for i in range(len(self.points) - 1):
                p1 = to_screen(self.points[i][0], self.points[i][1])
                p2 = to_screen(self.points[i+1][0], self.points[i+1][1])
                painter.drawLine(int(p1[0]), int(p1[1]), int(p2[0]), int(p2[1]))

        # 2. Kreslení bodů
        painter.setFont(QFont("Monospace", 7))
        for x, y, color, label in self.points:
            sx, sy = to_screen(x, y)
            painter.setBrush(QColor(color))
            painter.setPen(QPen(color, 1))
            
            offset = (self.marker_size - self.scale) // 2
            painter.drawRect(int(sx - offset), int(sy - offset), 
                             self.marker_size, self.marker_size)

            if label is not None:
                painter.setPen(text_color)
                painter.drawText(int(sx + 5), int(sy - 2), str(label))

class ESS251Calculator(QMainWindow):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("ESS251 | Visual ECC Calculator")
        self.is_dark = True
        self.init_ui()

    def init_ui(self):
        central_widget = QWidget()
        self.setCentralWidget(central_widget)
        self.main_vbox = QVBoxLayout(central_widget)
        
        # Hlavní horizontální obsah
        content_layout = QHBoxLayout()

        # --- LEVÝ PANEL ---
        left_panel = QWidget()
        self.left_vbox = QVBoxLayout(left_panel)
        
        self.header = QLabel("ESS251 CALCULATOR")
        self.left_vbox.addWidget(self.header)

        # G Point
        self.add_section(self.left_vbox, "G: Base Point")
        g_grid = QGridLayout()
        self.gx = self.create_input(str(G_POINT[0]), g_grid, 0, "x:")
        self.gy = self.create_input(str(G_POINT[1]), g_grid, 1, "y:")
        self.left_vbox.addLayout(g_grid)

        # Multiplication
        self.add_section(self.left_vbox, "Scalar Multiplication")
        k_row = QHBoxLayout()
        self.k_input = QLineEdit("2")
        self.btn_mul = QPushButton("k × G")
        self.btn_mul.clicked.connect(self.handle_mul)
        k_row.addWidget(QLabel("k:"))
        k_row.addWidget(self.k_input)
        k_row.addWidget(self.btn_mul)
        self.left_vbox.addLayout(k_row)

        # Addition
        self.add_section(self.left_vbox, "Point Addition")
        pq_grid = QGridLayout()
        self.px = QLineEdit(str(G_POINT[0])); self.py = QLineEdit(str(G_POINT[1]))
        self.qx = QLineEdit("10"); self.qy = QLineEdit("118")
        pq_grid.addWidget(QLabel("P:"), 0, 0)
        pq_grid.addWidget(self.px, 0, 1); pq_grid.addWidget(self.py, 0, 2)
        pq_grid.addWidget(QLabel("Q:"), 1, 0)
        pq_grid.addWidget(self.qx, 1, 1); pq_grid.addWidget(self.qy, 1, 2)
        self.left_vbox.addLayout(pq_grid)
        self.btn_add = QPushButton("P + Q")
        self.btn_add.clicked.connect(self.handle_add)
        self.left_vbox.addWidget(self.btn_add)

        # Cycle
        self.add_section(self.left_vbox, "Cycle Analysis")
        self.cb_line = QCheckBox("Show lines between steps")
        self.cb_line.setChecked(True)
        self.cb_line.stateChanged.connect(self.sync_settings)
        self.left_vbox.addWidget(self.cb_line)
        
        self.btn_all = QPushButton("ALL 252 POINTS (Cycle)")
        self.btn_all.setObjectName("special_button")  # Pro speciální styl
        self.btn_all.clicked.connect(self.handle_all)
        self.left_vbox.addWidget(self.btn_all)

        # Log
        self.log = QTextEdit()
        self.log.setReadOnly(True)
        self.log.setObjectName("log_area")  # Pro identifikaci
        self.left_vbox.addWidget(self.log)

        content_layout.addWidget(left_panel, 1)

        # --- PRAVÝ PANEL ---
        right_panel = QWidget()
        right_vbox = QVBoxLayout(right_panel)
        self.canvas = PointCanvas()
        right_vbox.addWidget(self.canvas)

        self.btn_clear = QPushButton("CLEAR CANVAS")
        self.btn_clear.clicked.connect(self.canvas.clear_canvas)
        right_vbox.addWidget(self.btn_clear)

        content_layout.addWidget(right_panel, 2)
        
        # Přidání obsahu do hlavního vboxu
        self.main_vbox.addLayout(content_layout)

        # --- STAVOVÝ ŘÁDEK (Dole) ---
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
        
        self.main_vbox.addLayout(status_bar)

        self.apply_theme()

    def add_section(self, layout, title):
        lbl = QLabel(title)
        lbl.setProperty("is_section", True)  # Pro pozdější stylizaci
        layout.addWidget(lbl)
        line = QFrame()
        line.setFrameShape(QFrame.Shape.HLine)
        layout.addWidget(line)

    def create_input(self, val, layout, row, label):
        layout.addWidget(QLabel(label), row, 0)
        edit = QLineEdit(val)
        layout.addWidget(edit, row, 1)
        return edit

    def apply_theme(self):
        # Použití centralizovaných stylů z qt_ui
        qt_ui.apply_styles_to_widgets(self, self.is_dark)
        
        # Specifické styly
        self.header.setStyleSheet(qt_ui.get_header_style(self.is_dark, size=20))
        self.status_label.setStyleSheet(qt_ui.get_status_label_style(self.is_dark))
        self.log.setStyleSheet(qt_ui.get_text_edit_style(self.is_dark, variant="log"))
        
        # Speciální styl pro tlačítko cyklu
        cycle_color = "#004411" if self.is_dark else "#fee"
        self.btn_all.setStyleSheet(qt_ui.get_special_button_style(self.is_dark, bg_color=cycle_color))
        
        # Sekční labely
        for lbl in self.findChildren(QLabel):
            if lbl.property("is_section"):
                lbl.setStyleSheet(qt_ui.get_section_label_style(self.is_dark))
            elif lbl != self.header and lbl != self.status_label:
                lbl.setStyleSheet(qt_ui.get_label_style(self.is_dark))

        self.canvas.is_dark = self.is_dark
        self.canvas.update()

    def toggle_theme(self):
        self.is_dark = not self.is_dark
        self.apply_theme()

    def sync_settings(self):
        self.canvas.draw_lines = self.cb_line.isChecked()
        self.canvas.update()

    def handle_mul(self):
        try:
            k = int(self.k_input.text())
            G = (int(self.gx.text()), int(self.gy.text()))
            self.status_label.setText(f"Status: Calculating {k}G...")
            res = scalar_mult(k, G, A_PARAM, P_MOD, ORDER_N)
            if res:
                self.canvas.add_point(res, QColor(0, 255, 65), label=f"k={k}")
                self.log.append(f"Result k*G: {res}")
            self.status_label.setText("Status: Done")
        except Exception as e:
            self.log.append(f"Error: {e}")

    def handle_add(self):
        try:
            P = (int(self.px.text()), int(self.py.text()))
            Q = (int(self.qx.text()), int(self.qy.text()))
            res = point_adding(P, Q, P_MOD, A_PARAM)
            if res:
                self.canvas.add_point(P, QColor(255, 165, 0))
                self.canvas.add_point(Q, QColor(255, 165, 0))
                self.canvas.add_point(res, QColor(0, 255, 65), label="P+Q")
                self.log.append(f"Result P+Q: {res}")
            self.status_label.setText("Status: Points added")
        except Exception as e:
            self.log.append(f"Error: {e}")

    def handle_all(self):
        self.canvas.clear_canvas()
        try:
            G = (int(self.gx.text()), int(self.gy.text()))
            self.status_label.setText("Status: Running full cycle (252)...")
            for k in range(1, ORDER_N + 1):
                res = scalar_mult(k, G, A_PARAM, P_MOD, ORDER_N)
                if res:
                    color = QColor.fromHsl((k * 2) % 360, 200, 120)
                    self.canvas.add_point(res, color, label=k)
                else:
                    self.log.append(f"Reached Point at Infinity at k={k}")
                    break
            self.log.append("Cycle calculation finished.")
            self.status_label.setText("Status: Cycle complete")
        except Exception as e:
            self.log.append(f"Error: {e}")

if __name__ == "__main__":
    app = QApplication(sys.argv)
    window = ESS251Calculator()
    window.resize(1150, 850)
    window.show()
    sys.exit(app.exec())
