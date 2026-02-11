// ==============================
// Bit·Block·Rithm – Wallet Test
// ==============================

let circleSize = 64;

// ------------------------------
// SAVE
// ------------------------------
function saveValue() {
  const timestamp = Date.now();
  const record = `${timestamp}, ${circleSize}`;

  localStorage.setItem(timestamp.toString(), record);

  document.getElementById("status").textContent =
    `saved: ${record}`;

  renderTable();
}

// ------------------------------
// DELETE
// ------------------------------
function deleteRecord(id) {
  if (!confirm(`Smazat záznam pro ID ${id}?`)) return;

  fetch(`test_delete.php?id=${id}`)
    .then(r => r.text())
    .then(() => {
      localStorage.removeItem(id);
      renderTable();
    })
    .catch(err => {
      alert("Delete failed");
      console.error(err);
    });
}

// ------------------------------
// TABLE RENDER
// ------------------------------
function renderTable() {
  const tbody = document.getElementById("wallet-table-body");
  tbody.innerHTML = "";

  const keys = Object.keys(localStorage)
    .sort((a, b) => Number(b) - Number(a));

  keys.forEach((key, index) => {
    const value = localStorage.getItem(key);
    if (!value.includes(",")) return;

    const [tsRaw, sizeRaw] = value.split(",");
    const ts = new Date(Number(tsRaw)).toLocaleString();
    const size = sizeRaw.trim();

    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${index + 1}</td>
      <td>${ts}</td>
      <td>${size}</td>
      <td>
        <button class="delete-btn"
          onclick="deleteRecord('${key}')">🗑</button>
      </td>
    `;
    tbody.appendChild(row);
  });
}

// ------------------------------
// p5.js
// ------------------------------
new p5(p => {
  p.setup = () => {
    const canvas = p.createCanvas(200, 200);
    canvas.parent("p5-holder");
    p.textAlign(p.CENTER, p.CENTER);
    p.textSize(24);
  };

  p.draw = () => {
    p.background(20);

    p.noFill();
    p.stroke(200);
    p.circle(p.width / 2, p.height / 2, circleSize);

    p.noStroke();
    p.fill(0, 255, 0);
    p.text(circleSize, p.width / 2, p.height / 2);
  };

  p.mouseWheel = e => {
    circleSize -= Math.sign(e.delta) * 4;
    circleSize = p.constrain(circleSize, 10, 180);
    return false;
  };
});

// ------------------------------
// INIT
// ------------------------------
window.addEventListener("load", renderTable);
