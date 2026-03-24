from obt.ash24 import ASH24, bytes_to_bin24, print_bit_hash


# =================================
print("A", hex(ASH24(b"A")))
print("AGAMA", hex(ASH24(b"AGAMA")))
print("Agama", hex(ASH24(b"Agama")))
print("Agama is testing a hash function concept.", hex(ASH24(b"Agama is testing a hash function concept.")))
print("Agama is testing a hash function concept:", hex(ASH24(b"Agama is testing a hash function concept")))


print("="*30)
print_bit_hash(1)
print_bit_hash(256)
print_bit_hash(1023)
print_bit_hash(0xABCD)


print("--- test --- bitstring | number")
i1 = b"\x01"
# print("i1", format(i1[0], "08b"))   # 00000001
print("b01",hex(ASH24(b"\x01")))

h1 = ASH24(bytes([1]))
h2 = ASH24(bytes([1, 0]))  # 256 = 0x100


print("="*30)

def print_bit_hash(num: int):
    # Print input number and ASH16 hash in decimal, hex, and 16-bit binary.
    # vždy 2 bajty vstup pro konzistenci
    data = num.to_bytes(2, byteorder="big")
    h = ASH24(data)
    print(f"{num} (0x{num:04x}) | {bytes_to_bin24(data)} (0x{h:04x}) | {format(h, '024b')})")
