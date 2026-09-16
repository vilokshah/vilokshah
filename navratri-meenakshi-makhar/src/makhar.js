import * as THREE from "three";
import { GODDESSES } from "./goddesses.js";

const C = {
  gold: 0xd4af37,
  maroon: 0x6b1d1a,
};

function mat(color, extras = {}) {
  return new THREE.MeshStandardMaterial({
    color,
    roughness: 0.45,
    metalness: 0.12,
    ...extras,
  });
}

function goldMat(metalness = 0.82) {
  return new THREE.MeshStandardMaterial({
    color: C.gold,
    roughness: 0.3,
    metalness,
    emissive: 0x3a2808,
    emissiveIntensity: 0.18,
  });
}

function marbleMat() {
  return new THREE.MeshStandardMaterial({
    color: 0xf4f1ea,
    roughness: 0.22,
    metalness: 0.08,
  });
}

export function drawBannerCanvas(images, W = 2400, H = 2200) {
  const canvas = document.createElement("canvas");
  canvas.width = W;
  canvas.height = H;
  const ctx = canvas.getContext("2d");

  ctx.drawImage(images.bannerBg, 0, 0, W, H);

  const holeX = (4 / 12) * W;
  const holeW = (4 / 12) * W;
  const holeH = (4 / 11) * H;
  const holeY = H - holeH;

  const grd = ctx.createLinearGradient(holeX, holeY, holeX, holeY + holeH);
  grd.addColorStop(0, "#f7f1e4");
  grd.addColorStop(1, "#e8dcc8");
  ctx.fillStyle = grd;
  ctx.fillRect(holeX, holeY, holeW, holeH);

  ctx.strokeStyle = "#c9a227";
  ctx.lineWidth = 14;
  ctx.strokeRect(holeX + 18, holeY + 18, holeW - 36, holeH - 36);

  const cx = holeX + holeW / 2;
  const cy = holeY + holeH / 2;
  ctx.strokeStyle = "rgba(201,162,39,0.7)";
  for (let r = 40; r <= 280; r += 48) {
    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, Math.PI * 2);
    ctx.stroke();
  }
  for (let i = 0; i < 12; i += 1) {
    const a = (i / 12) * Math.PI * 2;
    ctx.beginPath();
    ctx.ellipse(cx + Math.cos(a) * 150, cy + Math.sin(a) * 150, 38, 16, a, 0, Math.PI * 2);
    ctx.fillStyle = "rgba(222,107,138,0.35)";
    ctx.fill();
  }

  GODDESSES.forEach((g) => {
    const img = images[g.id];
    const fw = g.day === 5 ? 1.72 : 1.55;
    const fh = g.day === 5 ? 2.15 : 1.95;
    const x = ((g.x + 6) / 12) * W;
    const y = ((11 - g.y) / 11) * H;
    const pw = (fw / 12) * W;
    const ph = (fh / 11) * H;
    const left = x - pw / 2;
    const top = y - ph / 2;

    ctx.fillStyle = "#c9a227";
    roundRect(ctx, left - 10, top - 10, pw + 20, ph + 46, 12);
    ctx.fill();
    ctx.drawImage(img, left, top, pw, ph);

    ctx.fillStyle = "#6b1a16";
    ctx.fillRect(left - 6, top + ph, pw + 12, 32);
    ctx.fillStyle = "#fff4d4";
    ctx.font = "600 22px Cinzel, serif";
    ctx.textAlign = "center";
    ctx.fillText(`DAY ${g.day}  ·  ${g.name.toUpperCase()}`, x, top + ph + 23);
  });

  ctx.fillStyle = "rgba(18,10,8,0.72)";
  ctx.fillRect(W * 0.22, 36, W * 0.56, 70);
  ctx.strokeStyle = "#e8c547";
  ctx.lineWidth = 3;
  ctx.strokeRect(W * 0.22, 36, W * 0.56, 70);
  ctx.fillStyle = "#fff6de";
  ctx.font = "600 36px Cinzel, serif";
  ctx.textAlign = "center";
  ctx.fillText("MEENAKSHI  ·  NAVARĀTRI  FLEX  12 × 11 FT", W / 2, 82);

  return canvas;
}

export function composeBannerTexture(images) {
  const canvas = drawBannerCanvas(images);
  const tex = new THREE.CanvasTexture(canvas);
  tex.colorSpace = THREE.SRGBColorSpace;
  tex.anisotropy = 8;
  return tex;
}

function roundRect(ctx, x, y, w, h, r) {
  ctx.beginPath();
  ctx.moveTo(x + r, y);
  ctx.arcTo(x + w, y, x + w, y + h, r);
  ctx.arcTo(x + w, y + h, x, y + h, r);
  ctx.arcTo(x, y + h, x, y, r);
  ctx.arcTo(x, y, x + w, y, r);
  ctx.closePath();
}

export function createMakhar(textures, bannerMap) {
  const root = new THREE.Group();
  const lampLights = [];

  const banner = new THREE.Mesh(
    new THREE.PlaneGeometry(12, 11),
    new THREE.MeshStandardMaterial({
      map: bannerMap,
      roughness: 0.55,
      metalness: 0.04,
    })
  );
  banner.position.set(0, 5.5, -0.02);
  root.add(banner);

  const rail = new THREE.Mesh(new THREE.BoxGeometry(12.2, 0.12, 0.12), goldMat());
  rail.position.set(0, 11.08, 0.02);
  root.add(rail);

  const niches = GODDESSES.map((g) => {
    const niche = createHitFrame(g, textures[g.id]);
    root.add(niche.group);
    return niche;
  });

  const temple = createMarbleTemple(textures.marbleFront);
  temple.position.set(0, 0, 1.15);
  root.add(temple);

  [-5.2, 5.2].forEach((x) => {
    const light = new THREE.PointLight(0xffc978, 4.2, 8, 1.6);
    light.position.set(x, 10.2, 1.2);
    root.add(light);
    lampLights.push(light);
  });

  return { root, niches, lampLights };
}

function createHitFrame(goddess, texture) {
  const group = new THREE.Group();
  const w = goddess.day === 5 ? 1.72 : 1.55;
  const h = goddess.day === 5 ? 2.15 : 1.95;
  group.position.set(goddess.x, goddess.y, 0.04);
  group.userData.goddessId = goddess.id;

  const hit = new THREE.Mesh(
    new THREE.PlaneGeometry(w, h),
    new THREE.MeshBasicMaterial({
      map: texture,
      transparent: true,
      opacity: 0.0,
    })
  );
  hit.userData.goddessId = goddess.id;
  group.add(hit);

  const highlight = new THREE.Mesh(
    new THREE.PlaneGeometry(w + 0.12, h + 0.28),
    new THREE.MeshBasicMaterial({
      color: C.gold,
      transparent: true,
      opacity: 0,
    })
  );
  highlight.position.z = -0.01;
  group.add(highlight);

  return { group, hit, highlight, id: goddess.id };
}

function createMarbleTemple(frontMap) {
  const g = new THREE.Group();

  const sides = marbleMat();
  const depth = 1.65;
  const shell = new THREE.Mesh(new THREE.BoxGeometry(4, 4, depth), sides);
  shell.position.y = 2;
  g.add(shell);

  const front = new THREE.Mesh(
    new THREE.PlaneGeometry(4, 4),
    new THREE.MeshStandardMaterial({
      map: frontMap,
      roughness: 0.28,
      metalness: 0.06,
    })
  );
  front.position.set(0, 2, depth / 2 + 0.01);
  g.add(front);

  return g;
}

export function createRoom() {
  const g = new THREE.Group();
  const floor = new THREE.Mesh(
    new THREE.PlaneGeometry(22, 16),
    mat(0x4a4038, { roughness: 0.8 })
  );
  floor.rotation.x = -Math.PI / 2;
  g.add(floor);

  const wall = new THREE.Mesh(new THREE.PlaneGeometry(16, 13), mat(0x2a1810));
  wall.position.set(0, 6.5, -0.2);
  g.add(wall);

  const left = new THREE.Mesh(new THREE.PlaneGeometry(16, 13), mat(0x24140f));
  left.rotation.y = Math.PI / 2;
  left.position.set(-8, 6.5, 7.8);
  g.add(left);

  const right = left.clone();
  right.position.x = 8;
  right.rotation.y = -Math.PI / 2;
  g.add(right);

  return g;
}
