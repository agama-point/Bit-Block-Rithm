let slider;
let value = 50;

function setup() {
  const canvas = createCanvas(300, 300);
  canvas.parent("p5-holder");

  slider = createSlider(10, 140, value);
  slider.parent("p5-holder");
}

function draw() {
  background(0);
  value = slider.value();

  fill(0);
  stroke(0, 255, 0);
  strokeWeight(2);
  ellipse(width / 2, height / 2, value * 2);
}

function saveValue() {
  fetch("save.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ value })
  })
  .then(r => r.json())
  .then(d => {
    document.getElementById("status").innerText = d.ok ? "saved" : "error";
  });
}
