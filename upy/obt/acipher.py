# obt/acipher.py
# simple/basic Agama ciphers | ESP32 | MicroPython
ver = "0.1-2023-11"

import hashlib
import ubinascii
import os

DEBUG = False
BASE58_ALPHABET = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz"

_mnemonic_key = "AgamaPoint"
# AgamaPoint:
# 6cfdf76d0f2112db138c801825051e99efc8e7affc532fce4a088ad19844418e

def _vprint(*args):
    if DEBUG:
        print("[DEBUG]", *args)

def set_key(new_key):
    global _mnemonic_key
    _vprint(f"Key updated to: '{new_key}'")
    _mnemonic_key = new_key

def get_key():
    return _mnemonic_key

def _get_hash():
    # Helper to get the current key's hash
    return hashlib.sha256(_mnemonic_key.encode('utf-8')).digest()

def ac_xor(text):
    _vprint(f"Input text: '{text}'")
    text_bytes = text.encode('utf-8')
    input_len = len(text_bytes)
    separator = "/+"
    sep_len = len(separator)
    if DEBUG:
        print("len:", input_len)
    
    if input_len + sep_len <= 16:
        target_len = 16
    elif input_len + sep_len <= 32:
        target_len = 32
    else:
        raise ValueError("String too long!")

    payload = text + separator
    pad_len = target_len - len(payload)
    if pad_len > 0:
        padding = "".join([BASE58_ALPHABET[b % 58] for b in os.urandom(pad_len)])
        payload += padding
    
    key_hash = _get_hash()
    
    if DEBUG:
        print("hash:", key_hash.hex())
        
    payload_bytes = payload.encode('utf-8')
    result = bytearray()
    for i in range(len(payload_bytes)):
        result.append(payload_bytes[i] ^ key_hash[i])
    
    if DEBUG:
        print("text:", payload_bytes.hex())
    
    return ubinascii.hexlify(result).decode()

def ac_xor_decrypt(hex_str):
    # All logic moved here
    cipher_bytes = ubinascii.unhexlify(hex_str)
    key_hash = _get_hash()
    
    dec_payload = bytearray()
    for i in range(len(cipher_bytes)):
        dec_payload.append(cipher_bytes[i] ^ key_hash[i])
        
    full_str = dec_payload.decode('utf-8')
    return full_str.split("/+")[0]

def ac_caesar(text, s=13, up=True):
    _vprint(f"Caesar input: '{text}', shift: {s}")
    result = ""
    for char in text:
        if ord(char) == 32:
            result += " "
        elif char.isupper():
            result += chr((ord(char) + s - 65) % 26 + 65)
        elif char.islower():
            result += chr((ord(char) + s - 97) % 26 + 97)
        else:
            result += char
    return result.upper() if up else result