# qt_ui.py
"""
Centralizovaný modul pro PyQt6 UI styly a barevné schéma.
Podporuje dark a light mode s konzistentním vzhledem napříč aplikacemi.
"""

from PyQt6.QtGui import QColor

# ============================================================================
# BAREVNÁ PALETA
# ============================================================================

COLORS = {
    # Dark mode
    "dark_bg": "#1a1a1a",
    "dark_fg": "#00ff41",
    "dark_btn": "#252525",
    "dark_accent": "#ffa500",
    "dark_text": "#a0a0a0",
    "dark_text_dim": "#888888",
    "dark_border": "#00ff41",
    "dark_grid": "#282828",
    "dark_hover": "#004411",
    
    # Light mode
    "light_bg": "#f0f0f0",
    "light_fg": "#000000",
    "light_btn": "#e0e0e0",
    "light_accent": "#800000",
    "light_text": "#3c3c3c",
    "light_text_dim": "#666666",
    "light_border": "#800000",
    "light_grid": "#d2d2d2",
    "light_hover": "#d0d0d0",
}

# ============================================================================
# HELPER FUNKCE PRO ZÍSKÁNÍ BAREV
# ============================================================================

def get_bg_color(is_dark):
    """Vrátí QColor pro pozadí aplikace."""
    return QColor(COLORS["dark_bg"] if is_dark else COLORS["light_bg"])

def get_fg_color(is_dark):
    """Vrátí QColor pro hlavní popředí (texty, ikony)."""
    return QColor(COLORS["dark_fg"] if is_dark else COLORS["light_fg"])

def get_text_color(is_dark):
    """Vrátí QColor pro běžný text."""
    return QColor(COLORS["dark_text"] if is_dark else COLORS["light_text"])

def get_text_dim_color(is_dark):
    """Vrátí QColor pro ztlumený text (status bar, apod.)."""
    return QColor(COLORS["dark_text_dim"] if is_dark else COLORS["light_text_dim"])

def get_accent_color(is_dark):
    """Vrátí QColor pro akcenty."""
    return QColor(COLORS["dark_accent"] if is_dark else COLORS["light_accent"])

def get_border_color(is_dark):
    """Vrátí string barvy pro okraje."""
    return COLORS["dark_border"] if is_dark else COLORS["light_border"]

def get_grid_color(is_dark):
    """Vrátí QColor pro mřížky a oddělovače."""
    return QColor(COLORS["dark_grid"] if is_dark else COLORS["light_grid"])

def get_canvas_bg_color(is_dark):
    """Vrátí QColor pro pozadí canvas/vizualizačních ploch."""
    return QColor(15, 15, 15) if is_dark else QColor(240, 240, 240)

def get_circle_color(is_dark):
    """Vrátí QColor pro kruhy a pomocné grafické elementy."""
    return QColor(50, 50, 50) if is_dark else QColor(210, 210, 210)

# ============================================================================
# STYLY PRO HLAVNÍ APLIKACI
# ============================================================================

def get_main_style(is_dark):
    """Základní styl pro QMainWindow a hlavní widget."""
    bg = COLORS['dark_bg'] if is_dark else COLORS['light_bg']
    fg = COLORS['dark_fg'] if is_dark else COLORS['light_fg']
    return f"background-color: {bg}; color: {fg};"

# ============================================================================
# STYLY PRO TLAČÍTKA
# ============================================================================

def get_button_style(is_dark):
    """Standardní styl pro QPushButton."""
    if is_dark:
        return f"""
            QPushButton {{
                background-color: {COLORS['dark_btn']};
                color: {COLORS['dark_fg']};
                border: 1px solid {COLORS['dark_border']};
                padding: 8px;
                font-weight: bold;
                border-radius: 3px;
            }}
            QPushButton:hover {{ background-color: {COLORS['dark_hover']}; }}
            QPushButton:pressed {{ background-color: {COLORS['dark_fg']}; color: #000; }}
        """
    else:
        return f"""
            QPushButton {{
                background-color: {COLORS['light_btn']};
                color: {COLORS['light_accent']};
                border: 1px solid {COLORS['light_border']};
                padding: 8px;
                font-weight: bold;
                border-radius: 3px;
            }}
            QPushButton:hover {{ background-color: {COLORS['light_hover']}; }}
        """

def get_theme_button_style(is_dark):
    """Styl pro malé theme toggle tlačítko v status baru."""
    base_style = get_button_style(is_dark)
    return base_style + "QPushButton { padding: 2px; font-size: 10px; }"

def get_preset_button_style(is_dark):
    """Styl pro malá preset tlačítka (čísla)."""
    base_style = get_button_style(is_dark)
    return base_style + "QPushButton { padding: 4px; }"

def get_special_button_style(is_dark, bg_color=None):
    """Styl pro speciální tlačítka s vlastním pozadím."""
    base_style = get_button_style(is_dark)
    if bg_color is None:
        bg_color = "#004411" if is_dark else "#fee"
    return base_style + f"QPushButton {{ background-color: {bg_color}; }}"

# ============================================================================
# STYLY PRO VSTUPNÍ POLE
# ============================================================================

def get_input_style(is_dark):
    """Styl pro QLineEdit."""
    bg = "#000" if is_dark else "#fff"
    fg = COLORS['dark_fg'] if is_dark else COLORS['light_fg']
    border = f"1px solid {COLORS['dark_border']}" if is_dark else f"1px solid {COLORS['light_border']}"
    return f"background: {bg}; color: {fg}; border: {border}; padding: 5px;"

# ============================================================================
# STYLY PRO LABELY
# ============================================================================

def get_label_style(is_dark):
    """Standardní styl pro QLabel."""
    color = COLORS['dark_fg'] if is_dark else COLORS['light_text_dim']
    return f"color: {color};"

def get_header_style(is_dark, size=20):
    """Styl pro hlavní nadpisy."""
    color = COLORS['dark_fg']
    return f"color: {color}; font-size: {size}px; font-weight: bold;"

def get_section_label_style(is_dark):
    """Styl pro nadpisy sekcí."""
    color = COLORS['dark_accent'] if is_dark else COLORS['light_accent']
    return f"color: {color}; font-weight: bold; margin-top: 10px;"

def get_info_label_style(is_dark):
    """Styl pro info labely (např. 'Modular Inverse')."""
    color = COLORS['dark_fg'] if is_dark else COLORS['light_accent']
    return f"color: {color}; font-size: 16px; font-weight: bold; margin-bottom: 5px;"

def get_status_label_style(is_dark):
    """Styl pro status bar label."""
    return "color: #888;"

# ============================================================================
# STYLY PRO TEXTOVÉ OBLASTI
# ============================================================================

def get_text_edit_style(is_dark, variant="log"):
    """
    Styl pro QTextEdit.
    variant: 'log' nebo 'code'
    """
    bg = "#050505" if is_dark else "#fff"
    fg = COLORS['dark_fg'] if is_dark else "#333"
    
    if variant == "code":
        bg = "#0f0f0f" if is_dark else "#fff"
        fg = "#888" if is_dark else "#333"
        return f"background: {bg}; color: {fg}; border: 1px solid #333; font-family: monospace;"
    else:  # log
        return f"background: {bg}; color: {fg}; border: 1px solid #333; font-family: 'Courier New';"

# ============================================================================
# STYLY PRO CHECKBOXY
# ============================================================================

def get_checkbox_style(is_dark):
    """Styl pro QCheckBox."""
    color = COLORS['dark_fg'] if is_dark else COLORS['light_text']
    return f"""
        QCheckBox {{
            color: {color};
            spacing: 5px;
        }}
        QCheckBox::indicator {{
            width: 15px;
            height: 15px;
            border: 1px solid {COLORS['dark_border'] if is_dark else COLORS['light_border']};
            border-radius: 2px;
            background-color: {"#000" if is_dark else "#fff"};
        }}
        QCheckBox::indicator:checked {{
            background-color: {COLORS['dark_fg'] if is_dark else COLORS['light_accent']};
        }}
    """

# ============================================================================
# STYLY PRO SLIDER
# ============================================================================

def get_slider_style(is_dark):
    """Styl pro QSlider."""
    if is_dark:
        return f"""
            QSlider::groove:horizontal {{
                border: 1px solid {COLORS['dark_border']};
                height: 8px;
                background: #000;
                margin: 2px 0;
                border-radius: 4px;
            }}
            QSlider::handle:horizontal {{
                background: {COLORS['dark_fg']};
                border: 1px solid {COLORS['dark_border']};
                width: 18px;
                margin: -5px 0;
                border-radius: 9px;
            }}
            QSlider::handle:horizontal:hover {{
                background: {COLORS['dark_accent']};
            }}
            QSlider::sub-page:horizontal {{
                background: {COLORS['dark_fg']};
                border: 1px solid {COLORS['dark_border']};
                height: 8px;
                border-radius: 4px;
            }}
        """
    else:
        return f"""
            QSlider::groove:horizontal {{
                border: 1px solid {COLORS['light_border']};
                height: 8px;
                background: #fff;
                margin: 2px 0;
                border-radius: 4px;
            }}
            QSlider::handle:horizontal {{
                background: {COLORS['light_accent']};
                border: 1px solid {COLORS['light_border']};
                width: 18px;
                margin: -5px 0;
                border-radius: 9px;
            }}
            QSlider::handle:horizontal:hover {{
                background: {COLORS['light_fg']};
            }}
            QSlider::sub-page:horizontal {{
                background: {COLORS['light_accent']};
                border: 1px solid {COLORS['light_border']};
                height: 8px;
                border-radius: 4px;
            }}
        """

# ============================================================================
# STYLY PRO FRAME (ODDĚLOVAČE)
# ============================================================================

def get_frame_style(is_dark):
    """Styl pro QFrame používané jako oddělovače."""
    color = COLORS['dark_border'] if is_dark else COLORS['light_border']
    return f"QFrame {{ border: none; background-color: {color}; }}"

# ============================================================================
# STYLY PRO SCROLL AREA
# ============================================================================

def get_scroll_area_style(is_dark):
    """Styl pro QScrollArea."""
    bg = COLORS['dark_bg'] if is_dark else COLORS['light_bg']
    return f"QScrollArea {{ background-color: {bg}; border: none; }}"

# ============================================================================
# APLIKACE STYLŮ NA WIDGETY
# ============================================================================

def apply_styles_to_widgets(window, is_dark):
    """
    Pomocná funkce pro aplikaci všech stylů na widgety v okně.
    
    Args:
        window: QMainWindow instance
        is_dark: bool - True pro dark mode, False pro light mode
    """
    from PyQt6.QtWidgets import QPushButton, QLineEdit, QLabel, QTextEdit, QCheckBox, QSlider, QFrame, QScrollArea
    
    # Aplikace základního stylu na okno
    window.setStyleSheet(get_main_style(is_dark))
    
    # Tlačítka
    for btn in window.findChildren(QPushButton):
        if btn.objectName() == "theme_button":
            btn.setStyleSheet(get_theme_button_style(is_dark))
        elif btn.objectName() == "preset_button":
            btn.setStyleSheet(get_preset_button_style(is_dark))
        elif btn.objectName() == "special_button":
            btn.setStyleSheet(get_special_button_style(is_dark))
        else:
            btn.setStyleSheet(get_button_style(is_dark))
    
    # Vstupní pole
    for edit in window.findChildren(QLineEdit):
        edit.setStyleSheet(get_input_style(is_dark))
    
    # Checkboxy
    for cb in window.findChildren(QCheckBox):
        cb.setStyleSheet(get_checkbox_style(is_dark))
    
    # Slidery
    for slider in window.findChildren(QSlider):
        slider.setStyleSheet(get_slider_style(is_dark))
    
    # Scroll areas
    for scroll in window.findChildren(QScrollArea):
        scroll.setStyleSheet(get_scroll_area_style(is_dark))

# ============================================================================
# KONSTANTY PRO ROZMĚRY
# ============================================================================

DIMENSIONS = {
    "theme_button_width": 80,
    "preset_button_width": 40,
    "status_bar_margin": 5,
    "section_margin_top": 10,
}
