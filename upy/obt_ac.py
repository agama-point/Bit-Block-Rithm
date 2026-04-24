from obt import acipher

def run_xor_test(label, text):
    print(f"\n--- {label} ---")
    try:
        # Encryption - One liner
        enc_hex = acipher.ac_xor(text)
        print(f"Encr. {enc_hex}")
        
        # Decryption - One liner
        clean_text = acipher.ac_xor_decrypt(enc_hex)
        print(f"Decrypted: {clean_text}")
        
    except Exception as e:
        print(f"Error: {e}")

print("-" * 33)

# 1. Short string
run_xor_test("[TEST]: SHORT STRING", "BEST TEST EVER")

# 2. Long string
run_xor_test("[TEST]: LONG STRING", "Kobyla ma maly bok")

# 3. Key Change Test
print("\n[TEST]: KEY CHANGE ---")
acipher.set_key("new-secret-key")
run_xor_test("TEST WITH NEW KEY", "BEST TEST EVER")

print("-" * 33)
print("[TEST]: CAESAR ---")
caesar_enc = acipher.ac_caesar("Hello ESP32", s=7, up=False)
print(f"Ciphered: {caesar_enc}")
# Caesar decrypt (using the same function with complementary shift)
caesar_dec = acipher.ac_caesar(caesar_enc, s=19, up=False)
print(f"Deciphered: {caesar_dec}")

"""
---------------------------------

--- [TEST]: SHORT STRING ---
[DEBUG] Input text: 'BEST TEST EVER'
len: 14
hash: bf11c007b3eddfa1ebcca54617f27c337e14ea840551537d8a239945b04abd00
text: 42455354205445535420455645522f2b
Encr. fd54935393b99af2bfece01052a05318
Decrypted: BEST TEST EVER

--- [TEST]: LONG STRING ---
[DEBUG] Input text: 'Kobyla ma maly bok'
len: 18
hash: bf11c007b3eddfa1ebcca54617f27c337e14ea840551537d8a239945b04abd00
text: 4b6f62796c61206d61206d616c7920626f6b2f2b70677277586a714573765550
Encr. f47ea27edf8cffcc8aecc8277b8b5c51117fc5af7536210ad249e800c33ce850
Decrypted: Kobyla ma maly bok

[TEST]: KEY CHANGE ---
[DEBUG] Key updated to: 'new-secret-key'

--- TEST WITH NEW KEY ---
[DEBUG] Input text: 'BEST TEST EVER'
len: 14
hash: de5ebd8db448609bb0a26b353a0ba10f15171a250a6b6a9eb15decba9314a29a
text: 42455354205445535420455645522f2b
Encr. 9c1beed9941c25c8e4822e637f598e24
Decrypted: BEST TEST EVER
---------------------------------
[TEST]: CAESAR ---
[DEBUG] Caesar input: 'Hello ESP32', shift: 7
Ciphered: Olssv LZW32
[DEBUG] Caesar input: 'Olssv LZW32', shift: 19
Deciphered: Hello ESP32
"""
