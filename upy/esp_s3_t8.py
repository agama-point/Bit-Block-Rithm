import sys
import select
from machine import Pin
from time import sleep, sleep_ms
from neopixel import NeoPixel
from components.led import Led
import ujson  
from obt.ess251 import scalar_mult, signToy, verifyToy, G_POINT, pubkey_to_addr, sig_to_hexa
from obt.ash24 import ASH24, bytes_to_bin24, print_bit_hash


ver = "t8_2603 :: 0.31"

# Setup LEDs
l1 = Led(14)
l2 = Led(48)
in1 = Pin(3)
in3 = Pin(9)

# --- button ---
counter=0
value = 0

def irq_handler(v):
    global value, counter
    counter += 1
    print(":: IRQ ", counter)
    #led.toggle()
    if counter == 1:
        print("READY")

    l2.value(value)
    value = 1 if value == 0 else 0

button0 = Pin(0, Pin.IN)
button0.irq(trigger=Pin.IRQ_FALLING, handler=irq_handler)

# --- RGB ---
class StatusLed(NeoPixel):
    _last_state = (0,0,0)

    def show_led(self, color, force=False):
        if self._last_state == color and not force:
            return
        self.fill(color)
        self.write()
        self._last_state = color


# Startup
print(":: start")
sleep(3)

def blink():
    l1.value(1)
    sleep(0.2)
    l1.value(0)
    l2.value(1)
    sleep(0.3)
    l2.value(0)

for i in range(3):
    print(i)
    blink()

led = StatusLed(Pin(38), 1)
led.show_led((20,0,0))
sleep_ms(250)


def test_key(k,nick):
    print(":: ["+nick+"]")
    priv = k
    pub = scalar_mult(priv, G_POINT)
    print(pub, pubkey_to_addr(pub))
    print("="*20)

def test_ess251():
    print(":: [Sign/Verify]")
    priv = 42
    pub = scalar_mult(priv, G_POINT)
    msg = 123
    sig = signToy(priv, msg, debug=True)
    valid = verifyToy(pub, msg, sig, debug=True)
    print(":: Signature valid:", valid)


t8k = 111
t8pub_point = scalar_mult(t8k, G_POINT)
t8pub = pubkey_to_addr(t8pub_point)

test_key(111,":: T8|Alice")
# test_key(222,"Bob")
print(":: ASH24: Agama", hex(ASH24(b"Agama")))
test_ess251()

print("READY")

i = 0

while True:
   # compute values
    val1 = i
    val2 = val1 * 2
    val3 = val1 * 3

    # build JSON string
    json_str = ujson.dumps({
        "val1": val1,
        "val2": val2,
        "val3": val3
    })

    # send to serial
    if (i%20 == 0):
        # print(json_str)
        none = True

    i += 1
    sleep(0.5)
    
    if select.select([sys.stdin], [], [], 0)[0]:
        line = sys.stdin.readline().strip()
        if line:
            print("::", line)
            try:
                data = ujson.loads(line)

                if "get" in data and data["get"] == "ver":     
                    print(ver)

                if "get" in data and data["get"] == "addr":     
                    print(t8pub)

                # LED
                if "led1" in data:
                    l1.value(1 if data["led1"] == "on" else 0)                
                if "led2" in data:
                    l2.value(1 if data["led2"] == "on" else 0)
                
                # Text -> patrola
                if "text" in data and data["text"] == "kdotam":
                    print(":: patrola")
                    counter = 0
                
                if "text" in data and data["text"] == "jaka":
                    resp = "vojenska"
                    print(ujson.dumps({"laudon_res": resp}))
                    
                if "sign" in data:
                    result = data["sign"]
                    msg = hex(ASH24(result.encode()))[2:]
                    print(":: ASH24: ", msg)
                    sig = signToy(t8k, msg, debug=True)
                    print(":: sig:", sig)
                    # print(sig_to_hexa(sig))
                    signature_hex = sig_to_hexa(sig)
                    print(ujson.dumps({"sig_res": signature_hex})) 
                    
                # Double -> return 2*value
                if "double" in data:
                    result = data["double"] * 2
                    print(result)

            except ValueError:
                print(":: Invalid JSON")
               
