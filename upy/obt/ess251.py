# obt/ess251.py
# ESS251 toy ECC library for MicroPython
# Version 0.33 | 2026/03

ESS251_VER = "0.33 | 2026/03"

P_MOD = 251
A_PARAM = 0
B_PARAM = 7
G_POINT = (10, 76)
ORDER_N = 252

ECC_PARAMS = {
    "p": P_MOD,
    "a": A_PARAM,
    "b": B_PARAM,
    "G": G_POINT,
    "n": ORDER_N
}

def modN(n, m):
    return ((n % m) + m) % m

def inv_mod(x, mod_val):
    x = modN(x, mod_val)
    for i in range(1, mod_val):
        if (x * i) % mod_val == 1:
            return i
    return None

def point_doubling(P, a=A_PARAM, p=P_MOD):
    if P is None:
        return None
    x, y = P
    if y == 0:
        return None
    num = modN(3 * x * x + a, p)
    den = inv_mod(2 * y, p)
    if den is None:
        return None
    slope = modN(num * den, p)
    x3 = modN(slope * slope - 2 * x, p)
    y3 = modN(slope * (x - x3) - y, p)
    return (x3, y3)

def point_adding(P1, P2, p=P_MOD, a=A_PARAM):
    if P1 is None:
        return P2
    if P2 is None:
        return P1
    x1, y1 = P1
    x2, y2 = P2
    if x1 == x2 and y1 != y2:
        return None
    if x1 == x2:
        return point_doubling(P1, a, p)
    num = modN(y2 - y1, p)
    den = inv_mod(modN(x2 - x1, p), p)
    if den is None:
        return None
    slope = modN(num * den, p)
    x3 = modN(slope * slope - x1 - x2, p)
    y3 = modN(slope * (x1 - x3) - y1, p)
    return (x3, y3)

def scalar_mult(k, P, a=A_PARAM, p=P_MOD, n=ORDER_N):
    result = None
    addend = P
    k = modN(k, n)
    while k > 0:
        if k & 1:
            result = point_adding(result, addend, p, a)
        addend = point_doubling(addend, a, p)
        k >>= 1
    return result

def signToy(private_key, msg_hash, debug=False):
    if isinstance(msg_hash, str):
        msg_hash = int(msg_hash, 16) if msg_hash.startswith("0x") else int(msg_hash, 16)
    k = (msg_hash ^ 0x55) % ORDER_N
    if k == 0:
        k = 1
    R_point = scalar_mult(k, G_POINT)
    r = modN(R_point[0], ORDER_N)
    e = modN(msg_hash, ORDER_N)
    s = modN(k + e * private_key, ORDER_N)
    if debug:
        print("\n[DEBUG-SIGN] msg_hash =", msg_hash)
        print("[DEBUG-SIGN] k =", k)
        print("[DEBUG-SIGN] R_point =", R_point)
        print("[DEBUG-SIGN] r =", r, "s =", s)
    return {"r": r, "s": s, "R_point": R_point}

def verifyToy(pubKeyPoint, msgHash, signature, debug=False):
    if isinstance(msgHash, str):
        msgHash = int(msgHash, 16) if msgHash.startswith("0x") else int(msgHash, 16)
    r = signature["r"]
    s = signature["s"]
    R_point = signature["R_point"]
    e = modN(msgHash, ORDER_N)
    L = scalar_mult(s, G_POINT)
    ePubKey = scalar_mult(e, pubKeyPoint)
    P = point_adding(R_point, ePubKey)
    if debug:
        fmt = lambda pt: f"[{pt[0]}, {pt[1]}]" if pt else "INF"
        print("\n[DEBUG-VERIFY] e =", e)
        print("[DEBUG-VERIFY] L = s*G:", fmt(L))
        print("[DEBUG-VERIFY] e*PubKey:", fmt(ePubKey))
        print("[DEBUG-VERIFY] P = R + e*PubKey:", fmt(P))
    if L is None or P is None:
        return L == P
    return L == P

def sig_to_hexa(signature):
    rHex = f"{signature['r']:02x}"
    sHex = f"{signature['s']:02x}"
    return rHex + sHex

def pubkey_to_addr(pub):
    return f"{pub[0]:02x}{pub[1]:02x}"

def hexa_to_point(hexstr):
    if len(hexstr) != 4:
        raise ValueError("Hex string must have length 4")
    x = int(hexstr[:2], 16)
    y = int(hexstr[2:], 16)
    return (x, y)