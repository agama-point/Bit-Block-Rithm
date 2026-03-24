# test_ess251.py
from obt.ess251 import scalar_mult, signToy, verifyToy, G_POINT, pubkey_to_addr


def test_key(k,nick):
    print("["+nick+"]")
    priv = k
    pub = scalar_mult(priv, G_POINT)
    print(pub, pubkey_to_addr(pub))
    print("="*20)

def test_ess251():
    print("[Sign/Verify]")
    priv = 42
    pub = scalar_mult(priv, G_POINT)
    msg = 123
    sig = signToy(priv, msg, debug=True)
    valid = verifyToy(pub, msg, sig, debug=True)
    print("Signature valid:", valid)


test_key(111,"Alice")
test_key(222,"Bob")
test_ess251()