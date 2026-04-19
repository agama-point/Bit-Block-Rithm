import sys
from PyQt6.QtWidgets import (
    QApplication, QWidget, QVBoxLayout, QHBoxLayout,
    QPushButton, QCheckBox, QLineEdit, QLabel, QTextEdit, QRadioButton
)
from PyQt6.QtGui import QPixmap
from PyQt6.QtCore import Qt

class MainWindow(QWidget):
    def __init__(self):
        super().__init__()

        self.setWindowTitle("AMC | test app")
        self.resize(600, 700)

        self.current_font_size = 15
        self.is_dark = True

        self.main_layout = QVBoxLayout()
        self.main_layout.setContentsMargins(20, 20, 20, 20)
        self.main_layout.setSpacing(15)

        # LOGO
        self.logo_label = QLabel()
        pixmap = QPixmap("bbr_obt.svg")
        if not pixmap.isNull():
            # V PyQt6 používáme Qt.TransformationMode.SmoothTransformation
            self.logo_label.setPixmap(pixmap.scaledToWidth(600, Qt.TransformationMode.SmoothTransformation))
        else:
            self.logo_label.setText("[ Logo bbr_obt.svg nenalezeno ]")
        
        self.logo_label.setAlignment(Qt.AlignmentFlag.AlignCenter)
        self.main_layout.addWidget(self.logo_label)

        self.label = QLabel("Status: ready")
        self.input = QLineEdit()
        self.input.setPlaceholderText("Napiš něco...")
        
        self.checkbox = QCheckBox("Enable")
        self.button = QPushButton("Send")

        # Velikost textu
        size_layout = QHBoxLayout()
        size_layout.addWidget(QLabel("Text size:"))
        
        self.radio_small = QRadioButton("Small")
        self.radio_middle = QRadioButton("Middle")
        self.radio_big = QRadioButton("Big")
        self.radio_middle.setChecked(True)

        self.radio_small.toggled.connect(lambda: self.set_font_size(11))
        self.radio_middle.toggled.connect(lambda: self.set_font_size(17))
        self.radio_big.toggled.connect(lambda: self.set_font_size(21))

        size_layout.addWidget(self.radio_small)
        size_layout.addWidget(self.radio_middle)
        size_layout.addWidget(self.radio_big)
        size_layout.addStretch()

        self.theme_toggle = QCheckBox("Dark mode")
        self.theme_toggle.setChecked(True)
        self.theme_toggle.stateChanged.connect(self.update_ui)

        self.textbox = QTextEdit()
        self.textbox.setReadOnly(True)
        self.textbox.setFixedHeight(250)
        self.textbox.setPlainText("\n".join(str(i) for i in range(1, 21)))

        self.button.clicked.connect(self.on_click)

        self.main_layout.addWidget(self.label)
        self.main_layout.addWidget(self.input)
        self.main_layout.addWidget(self.checkbox)
        self.main_layout.addWidget(self.button)
        self.main_layout.addLayout(size_layout)
        self.main_layout.addWidget(self.theme_toggle)
        self.main_layout.addWidget(self.textbox)

        self.setLayout(self.main_layout)
        self.update_ui()

    def set_font_size(self, size):
        self.current_font_size = size
        self.update_ui()

    def on_click(self):
        text = self.input.text()
        self.label.setText(f"Text: {text}, Checked: {self.checkbox.isChecked()}")

    def update_ui(self):
        self.is_dark = self.theme_toggle.isChecked()
        
        if self.is_dark:
            bg_color = "#2b2b2b"
            text_color = "#ffffff"
            input_bg = "#3b3b3b"
            accent_color = "#ffffff"
        else:
            bg_color = "#f0f0f0"
            text_color = "#000000"
            input_bg = "#ffffff"
            accent_color = "#000000"

        style = f"""
        QWidget {{
            background-color: {bg_color};
            color: {text_color};
            font-size: {self.current_font_size}px;
        }}
        
        QTextEdit, QLineEdit {{
            background-color: {input_bg};
            border: 1px solid {accent_color};
            padding: 4px;
        }}

        QPushButton {{
            background-color: {input_bg};
            border: 1px solid {accent_color};
            padding: 8px;
            border-radius: 4px;
        }}

        QCheckBox::indicator {{
            width: 18px;
            height: 18px;
            border: 2px solid {accent_color};
            background-color: transparent;
        }}
        QCheckBox::indicator:checked {{
            background-color: {accent_color};
        }}

        QRadioButton::indicator {{
            width: 18px;
            height: 18px;
            border: 2px solid {accent_color};
            border-radius: 11px;
            background-color: transparent;
        }}
        QRadioButton::indicator:checked {{
            background-color: {accent_color};
            border: 5px solid {bg_color};
        }}
        """
        self.setStyleSheet(style)

if __name__ == "__main__":
    app = QApplication(sys.argv)
    window = MainWindow()
    window.show()
    sys.exit(app.exec())