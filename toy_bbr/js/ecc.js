// --- Extended Euclidean algorithm
function euclid(sml, big) {
  if (sml === 0) {
    return [big, 0, 1];
  } else {
    const [g, y, x] = euclid(big % sml, sml);
    return [g, x - Math.floor(big / sml) * y, y];
  }
}

// --- multiplicative inverse mod n
function mult_inv(a, n) {
  const [g, x, _] = euclid(a, n);
  if (g !== 1) {
    throw new Error("multiplicative inverse does not exist");
  }
  return ((x % n) + n) % n;
}

// --- Point doubling
function point_doubling(px, py, a, p) {
  const s1 = (3 * px * px + a) % p;
  const s2 = mult_inv((2 * py) % p, p);
  const s = (s1 * s2) % p;

  const x = (s * s - 2 * px) % p;
  const y = (s * (px - x) - py) % p;
  return [(x + p) % p, (y + p) % p];
}

// --- Point adding
function point_adding(px, py, qx, qy, p) {
  const lam =
    ((qy - py + p) % p) *
    mult_inv((qx - px + p) % p, p);

  const rx = (lam * lam - px - qx) % p;
  const ry = (lam * (px - rx) - py) % p;
  return [(rx + p) % p, (ry + p) % p];
}
