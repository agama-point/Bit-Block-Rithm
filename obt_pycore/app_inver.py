#!/usr/bin/env python
# -*- coding: utf-8 -*-

import sys
import math
from PyQt6.QtWidgets import (QApplication, QMainWindow, QWidget, QVBoxLayout, 
                             QHBoxLayout, QSlider, QCheckBox, QLabel, QPushButton, QFrame)
from PyQt6.QtGui import QPainter, QPen, QColor, QFont
from PyQt6.QtCore import Qt, QPointF

# --- IMPORT EXTERNÍHO UI MODULU ---
import qt_ui

class ModularVisualizer(QWidget):
    def __init__(self):
        super().__init__()
        self.n = 7
        self.is_dark = True
        self.setMinimumSize(450, 450)

    def set_n(self, n):
        self.n = n
        self.update()

    def set_theme(self, is_dark):
        self.is_dark = is_dark
        self.update()

    def get_inverse(self, a, n):
        # Výpočet modulární inverze (brute force pro malé n)
        for x in range(1, n):
            if (a * x) % n == 1:
                return x
        return None

    def paintEvent(self, event):
        painter = QPainter(self)
        painter.setRenderHint(QPainter.RenderHint.Antialiasing)

        # Barvy z centralizovaného qt_ui
        bg_color = qt_ui.get_bg_color(self.is_dark)
        main_color = qt_ui.get_fg_color(self.is_dark)
        circle_color = qt_ui.get_circle_color(self.is_dark)
        text_color = qt_ui.get_text_color(self.is_dark)

        painter.fillRect(self.rect(), bg_color)

        w, h = self.width(), self.height()
        cx, cy = w / 2, h / 2
        r = min(w, h) * 0.38
        tick_len = 10

        # Hlavní kružnice (pozadí grafu)
        painter.setPen(QPen(circle_color, 1))
        painter.drawEllipse(QPointF(cx, cy), r, r)

        # Předvýpočet souřadnic bodů na kružnici
        points = []
        for i in range(self.n):
            angle = -math.pi / 2 + (i * 2 * math.pi / self.n)
            x = cx + r * math.cos(angle)
            y = cy + r * math.sin(angle)
            points.append((x, y, angle))

        # Kreslení spojnic (a -> a^-1)
        painter.setPen(QPen(main_color, 1))
        for i in range(1, self.n):
            inv = self.get_inverse(i, self.n)
            if inv and i <= inv:
                p1, p2 = points[i], points[inv]
                painter.drawLine(QPointF(p1[0], p1[1]), QPointF(p2[0], p2[1]))

        # Kreslení bodů, popisků a indikátorů chybějící inverze
        painter.setFont(QFont("Monospace", 8))
        for i in range(self.n):
            x, y, angle = points[i]
            
            # Tick mark (čárka ven z kruhu)
            x2 = cx + (r + tick_len) * math.cos(angle)
            y2 = cy + (r + tick_len) * math.sin(angle)
            painter.setPen(QPen(main_color, 1))
            painter.drawLine(QPointF(x, y), QPointF(x2, y2))

            # Číselný popisek
            tx = cx + (r + tick_len + 18) * math.cos(angle)
            ty = cy + (r + tick_len + 18) * math.sin(angle)
            painter.setPen(QPen(text_color))
            painter.drawText(int(tx - 12), int(ty - 12), 24, 24, 
                             Qt.AlignmentFlag.AlignCenter, str(i))

            # Červený bod pro prvky bez inverze (např. u složených čísel)
            if i != 0 and not self.get_inverse(i, self.n):
                painter.setBrush(QColor(255, 60, 60))
                painter.setPen(Qt.PenStyle.NoPen)
                painter.drawEllipse(QPointF(x, y), 3, 3)
                painter.setBrush(Qt.BrushStyle.NoBrush)

        # Titulek grafu dole
        painter.setPen(QPen(main_color))
        painter.setFont(QFont("SansSerif", 11, QFont.Weight.Bold))
        painter.drawText(self.rect(), Qt.AlignmentFlag.AlignBottom | Qt.AlignmentFlag.AlignHCenter, 
                         f"Modulo n = {self.n}")

class MainWindow(QMainWindow):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("ESS | Modular Inverse Visualizer")
        self.primes = self.build_primes()
        self.is_dark = True
        self.setup_ui()

    def setup_ui(self):
        main_widget = QWidget()
        self.setCentralWidget(main_widget)
        self.main_layout = QVBoxLayout(main_widget)

        # Prostřední layout pro ovládání a graf
        content_layout = QHBoxLayout()

        # --- LEVÝ OVLÁDACÍ PANEL ---
        self.left_panel = QVBoxLayout()
        
        self.label_info = QLabel("Modular Inverse\na · x ≡ 1 (mod n)")
        self.left_panel.addWidget(self.label_info)

        self.slider_label = QLabel(f"Value n: 7")
        self.left_panel.addWidget(self.slider_label)

        # Slider s podporou pro šipky
        self.slider = QSlider(Qt.Orientation.Horizontal)
        self.slider.setRange(2, 256)
        self.slider.setValue(7)
        self.slider.setSingleStep(1)
        self.slider.setPageStep(10)
        self.slider.valueChanged.connect(self.on_value_changed)
        self.left_panel.addWidget(self.slider)

        # Rychlé volby (Presets)
        presets_layout = QHBoxLayout()
        for val in [11, 17, 251]:
            btn = QPushButton(str(val))
            btn.setObjectName("preset_button")  # Pro identifikaci při aplikaci stylů
            btn.setFixedWidth(qt_ui.DIMENSIONS["preset_button_width"])
            btn.clicked.connect(lambda checked, v=val: self.slider.setValue(v))
            presets_layout.addWidget(btn)
        self.left_panel.addLayout(presets_layout)

        self.cb_primes = QCheckBox("Primes only")
        self.cb_primes.stateChanged.connect(self.on_value_changed)
        self.left_panel.addWidget(self.cb_primes)
        
        self.left_panel.addStretch()
        content_layout.addLayout(self.left_panel, 1)

        # --- PRAVÝ VIZUALIZAČNÍ PANEL ---
        self.visualizer = ModularVisualizer()
        content_layout.addWidget(self.visualizer, 3)

        self.main_layout.addLayout(content_layout)

        # --- STAVOVÁ LIŠTA (StatusBar) ---
        status_bar_layout = QHBoxLayout()
        status_bar_layout.setContentsMargins(
            qt_ui.DIMENSIONS["status_bar_margin"],
            qt_ui.DIMENSIONS["status_bar_margin"],
            qt_ui.DIMENSIONS["status_bar_margin"],
            qt_ui.DIMENSIONS["status_bar_margin"]
        )
        
        self.status_label = QLabel("Status: Ready")
        self.status_label.setFont(QFont("Arial", 8))
        
        self.btn_theme = QPushButton("Theme")
        self.btn_theme.setObjectName("theme_button")  # Pro identifikaci
        self.btn_theme.setFixedWidth(qt_ui.DIMENSIONS["theme_button_width"])
        self.btn_theme.clicked.connect(self.toggle_theme)
        
        status_bar_layout.addWidget(self.status_label)
        status_bar_layout.addStretch()
        status_bar_layout.addWidget(self.btn_theme)
        
        self.main_layout.addLayout(status_bar_layout)

        self.apply_theme()

    def build_primes(self):
        # Generování seznamu prvočísel pro filtraci slideru
        primes = []
        for n in range(2, 257):
            if all(n % i != 0 for i in range(2, int(n**0.5) + 1)):
                primes.append(n)
        return primes

    def on_value_changed(self):
        val = self.slider.value()
        
        # Logika pro "Primes only" filtr
        if self.cb_primes.isChecked():
            p_val = next((p for p in self.primes if p >= val), self.primes[-1])
            if p_val != val:
                self.slider.setValue(p_val)
                return
        
        self.slider_label.setText(f"Value n: {val}")
        self.visualizer.set_n(val)
        self.status_label.setText(f"Status: Displaying n={val}")

    def toggle_theme(self):
        self.is_dark = not self.is_dark
        self.apply_theme()
        self.visualizer.set_theme(self.is_dark)

    def apply_theme(self):
        # Použití centralizovaných stylů z qt_ui
        qt_ui.apply_styles_to_widgets(self, self.is_dark)
        
        # Specifické styly pro jednotlivé widgety
        self.label_info.setStyleSheet(qt_ui.get_info_label_style(self.is_dark))
        self.status_label.setStyleSheet(qt_ui.get_status_label_style(self.is_dark))
        
        # Aplikace stylů na běžné labely
        for lbl in self.findChildren(QLabel):
            if lbl != self.label_info and lbl != self.status_label:
                lbl.setStyleSheet(qt_ui.get_label_style(self.is_dark))

if __name__ == "__main__":
    app = QApplication(sys.argv)
    window = MainWindow()
    window.resize(850, 600)
    window.show()
    sys.exit(app.exec())
