import sys
import select
from machine import Pin
from time import sleep, sleep_ms

from neopixel import NeoPixel
from components.led import Led
import ujson  # MicroPython JSON knihovna

class StatusLed(NeoPixel):
    _last_state = (0,0,0)

    def show_led(self, color, force=False):
        if self._last_state == color and not force:
            return
        self.fill(color)
        self.write()
        self._last_state = color

# Setup LEDs
l1 = Led(14)
l2 = Led(48)
in1 = Pin(3)
in3 = Pin(9)

# Startup
print("start")
print(in1.value())
print(in3.value())
sleep(5)

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



i = 0

while True:
    # compute values
    val1 = i
    val2 = val1 * 2
    val3 = val1 * 3

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
        print(json_str)

    i += 1
    sleep(0.5)
    
    if select.select([sys.stdin], [], [], 0)[0]:
        line = sys.stdin.readline().strip()
        if line:
            print("Got:", line)
            try:
                data = ujson.loads(line)  # parse JSON
                if "led1" in data:
                    if data["led1"] == "on":
                        l1.value(1)
                    elif data["led1"] == "off":
                        l1.value(0)
            except ValueError:
                print("Invalid JSON")
    
    
"""
    # non-blocking read
    if select.select([sys.stdin], [], [], 0)[0]:
        line = sys.stdin.readline().strip()
        if line:
            print("ESP_Got:", line)
            if line == "led11":
                l1.value(1)
            elif line == "led10":
                l1.value(0)
            elif line == "led21":
                l2.value(1)
            elif line == "led20":
                l2.value(0)
            elif line == "kdotam":
                print("patrola")
                
 """               
