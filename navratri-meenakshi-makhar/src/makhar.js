import * as THREE from "three";
import { GODDESSES } from "./goddesses.js";

const C = {
  gold: 0xd4af37,
  goldDeep: 0x9a6b12,
  turquoise: 0x1b8a8a,
  coral: 0xd94a3c,
  magenta: 0xc2185b,
  emerald: 0x1f7a4d,
  saffron: 0xe09a3e,
  cream: 0xf3e2c0,
  stone: 0xc9a36a,
  pink: 0xe45a9a,
  teal: 0x0e5e5e,
  wood: 0x5a3318,
  night: 0x140c0a,
};

const PALETTE = [
  C.coral,
  C.turquoise,
  C.gold,
  C.magenta,
  C.emerald,
  C.saffron,
  C.pink,
  C.teal,
];

function mat(color, extras = {}) {
  return new THREE.MeshStandardMaterial({
    color,
    roughness: 0.45,
    metalness: 0.18,
    ...extras,
  });
}

function goldMat(metalness = 0.85) {
  return new THREE.MeshStandardMaterial({
    color: C.gold,
    roughness: 0.28,
    metalness,
    emissive: 0x3a2808,
    emissiveIntensity: 0.25,
  });
}

export function createMakhar(textures) {
  const root = new THREE.Group();
  root.name = "makhar";

  const wall = new THREE.Group();
  wall.position.y = 4.5;
  root.add(wall);

  // Back panel — 12 ft wide, 9 ft tall
  const back = new THREE.Mesh(
    new THREE.BoxGeometry(12, 9, 0.18),
    mat(0x6b1d1a)
  );
  back.position.z = -0.65;
  wall.add(back);

  const trim = new THREE.Mesh(
    new THREE.BoxGeometry(12.3, 9.3, 0.08),
    goldMat(0.7)
  );
  trim.position.z = -0.74;
  wall.add(trim);

  // Painted sculpture bands using the Meenakshi texture
  const bandGeo = new THREE.PlaneGeometry(11.6, 1.05);
  for (let i = 0; i < 6; i += 1) {
    const band = new THREE.Mesh(
      bandGeo,
      new THREE.MeshStandardMaterial({
        map: textures.gopuram,
        roughness: 0.55,
        metalness: 0.05,
      })
    );
    band.position.set(0, -3.6 + i * 1.35, -0.55);
    wall.add(band);
  }

  addLotusFrieze(wall, 0, -4.35, -0.42);
  addLotusFrieze(wall, 0, 4.28, -0.42);

  // Central 4x4 opening frame
  const opening = createSanctum(textures);
  opening.position.set(0, -0.5, 0);
  wall.add(opening);

  // Twin gopurams
  const leftTower = createGopuram(textures, 7.4, 2.35, 1.35);
  leftTower.position.set(-4.75, -4.5, 0.15);
  wall.add(leftTower);

  const rightTower = createGopuram(textures, 7.4, 2.35, 1.35);
  rightTower.position.set(4.75, -4.5, 0.15);
  wall.add(rightTower);

  // Niches: 4 left, 1 crown, 4 right
  const niches = [];
  const leftYs = [-3.05, -1.35, 0.35, 2.05];
  const rightYs = [2.05, 0.35, -1.35, -3.05];

  GODDESSES.slice(0, 4).forEach((g, i) => {
    niches.push(createNiche(g, textures[g.id], -2.55, leftYs[i], 0.22));
  });
  niches.push(createNiche(GODDESSES[4], textures.skandamata, 0, 3.35, 0.35, true));
  GODDESSES.slice(5).forEach((g, i) => {
    niches.push(createNiche(g, textures[g.id], 2.55, rightYs[i], 0.22));
  });

  niches.forEach((n) => wall.add(n.group));

  // Hanging lamps
  [-3.4, -1.15, 1.15, 3.4].forEach((x, i) => {
    const lamp = createDiyas();
    lamp.position.set(x, 3.85, 0.55);
    wall.add(lamp);
    const light = new THREE.PointLight(0xffc978, 4.5, 6, 1.6);
    light.position.set(x, 3.55, 0.7);
    wall.add(light);
  });

  addMarigoldSwags(wall);

  // Floor plinth and lotus tank suggestion
  const plinth = new THREE.Mesh(
    new THREE.BoxGeometry(12.6, 0.28, 2.2),
    mat(C.stone)
  );
  plinth.position.set(0, 0.14, 0.2);
  root.add(plinth);

  const tank = createLotusTank();
  tank.position.set(0, 0.3, 1.55);
  root.add(tank);

  // Steps to 4x4 shrine
  for (let i = 0; i < 3; i += 1) {
    const step = new THREE.Mesh(
      new THREE.BoxGeometry(4.4 - i * 0.2, 0.14, 0.42),
      goldMat(0.4)
    );
    step.position.set(0, 0.35 + i * 0.14, 0.95 - i * 0.22);
    root.add(step);
  }

  return { root, niches };
}

function createSanctum(textures) {
  const g = new THREE.Group();

  const recess = new THREE.Mesh(
    new THREE.BoxGeometry(4.2, 4.2, 1.1),
    mat(0x2a120c)
  );
  recess.position.z = -0.15;
  g.add(recess);

  const goldFrame = new THREE.Mesh(
    new THREE.BoxGeometry(4.55, 4.55, 0.16),
    goldMat()
  );
  goldFrame.position.z = 0.42;
  g.add(goldFrame);

  const inner = new THREE.Mesh(
    new THREE.BoxGeometry(4.05, 4.05, 0.08),
    mat(0x7a1f24)
  );
  inner.position.z = 0.5;
  g.add(inner);

  // Cut a visual opening with a dark plane and a placeholder temple
  const opening = new THREE.Mesh(
    new THREE.PlaneGeometry(3.7, 3.7),
    mat(0x1a0b08, { roughness: 0.9 })
  );
  opening.position.z = 0.56;
  g.add(opening);

  const cabinet = new THREE.Mesh(
    new THREE.BoxGeometry(2.2, 2.6, 0.7),
    goldMat(0.55)
  );
  cabinet.position.set(0, -0.35, 0.85);
  g.add(cabinet);

  const door = new THREE.Mesh(
    new THREE.PlaneGeometry(1.7, 2.1),
    new THREE.MeshStandardMaterial({
      color: 0x8b1e2d,
      roughness: 0.4,
      metalness: 0.2,
      emissive: 0x4a140c,
      emissiveIntensity: 0.35,
    })
  );
  door.position.set(0, -0.25, 1.21);
  g.add(door);

  const label = makeLabel("YOUR 4 × 4 FT TEMPLE", 2.4, 0.28);
  label.position.set(0, 1.55, 1.22);
  g.add(label);

  const kalash = createKalash();
  kalash.position.set(0, 2.35, 0.7);
  kalash.scale.setScalar(1.15);
  g.add(kalash);

  // Torana arch
  const arch = new THREE.Mesh(
    new THREE.TorusGeometry(2.15, 0.09, 10, 48, Math.PI),
    goldMat()
  );
  arch.rotation.z = Math.PI;
  arch.position.set(0, 0.15, 0.62);
  g.add(arch);

  return g;
}

function createGopuram(textures, height, baseW, depth) {
  const g = new THREE.Group();
  const storeys = 8;
  const h = height / storeys;

  const granite = new THREE.Mesh(
    new THREE.BoxGeometry(baseW * 1.05, 1.05, depth * 1.05),
    mat(0x8a6a45)
  );
  granite.position.y = 0.52;
  g.add(granite);

  const doorway = new THREE.Mesh(
    new THREE.BoxGeometry(0.55, 0.85, depth * 1.1),
    mat(0x1a0c08)
  );
  doorway.position.set(0, 0.5, 0);
  g.add(doorway);

  for (let i = 0; i < storeys; i += 1) {
    const t = i / storeys;
    const w = baseW * (1 - t * 0.62);
    const d = depth * (1 - t * 0.35);
    const y = 1.05 + i * h * 0.92 + h * 0.4;

    const storey = new THREE.Mesh(
      new THREE.BoxGeometry(w, h * 0.88, d),
      new THREE.MeshStandardMaterial({
        map: textures.gopuram,
        color: PALETTE[i % PALETTE.length],
        roughness: 0.5,
        metalness: 0.08,
      })
    );
    storey.position.y = y;
    g.add(storey);

    const cornice = new THREE.Mesh(
      new THREE.BoxGeometry(w * 1.08, 0.07, d * 1.08),
      goldMat(0.6)
    );
    cornice.position.y = y + h * 0.42;
    g.add(cornice);

    addMiniFigures(g, w, y, d * 0.52, i);
  }

  const roof = new THREE.Mesh(
    new THREE.CylinderGeometry(0.18, 0.18, baseW * 0.42, 16, 1, false, 0, Math.PI),
    goldMat()
  );
  roof.rotation.z = Math.PI / 2;
  roof.position.y = height + 0.15;
  g.add(roof);

  for (let k = -2; k <= 2; k += 1) {
    const finial = createKalash();
    finial.scale.setScalar(0.38);
    finial.position.set(k * 0.18, height + 0.42, 0);
    g.add(finial);
  }

  return g;
}

function addMiniFigures(parent, w, y, z, seed) {
  const cols = Math.max(4, Math.floor(w / 0.22));
  const geo = new THREE.CapsuleGeometry(0.045, 0.08, 3, 6);
  for (let i = 0; i < cols; i += 1) {
    const fig = new THREE.Mesh(geo, mat(PALETTE[(i + seed) % PALETTE.length]));
    const x = -w * 0.42 + (i / (cols - 1)) * w * 0.84;
    fig.position.set(x, y, z);
    parent.add(fig);
  }
}

function createNiche(goddess, texture, x, y, z, crown = false) {
  const group = new THREE.Group();
  group.position.set(x, y, z);
  group.userData.goddessId = goddess.id;

  const w = crown ? 1.55 : 1.28;
  const h = crown ? 1.7 : 1.42;

  const frame = new THREE.Mesh(
    new THREE.BoxGeometry(w + 0.16, h + 0.16, 0.12),
    goldMat()
  );
  group.add(frame);

  const well = new THREE.Mesh(
    new THREE.BoxGeometry(w, h, 0.16),
    mat(0x2a120e)
  );
  well.position.z = 0.02;
  group.add(well);

  const portrait = new THREE.Mesh(
    new THREE.PlaneGeometry(w * 0.88, h * 0.78),
    new THREE.MeshStandardMaterial({
      map: texture,
      roughness: 0.4,
      metalness: 0.05,
      emissive: 0x22110a,
      emissiveIntensity: 0.2,
    })
  );
  portrait.position.z = 0.12;
  portrait.position.y = 0.06;
  group.add(portrait);

  const caption = makeLabel(`DAY ${goddess.day}  ·  ${goddess.name.toUpperCase()}`, w + 0.05, 0.18);
  caption.position.set(0, -h * 0.42, 0.14);
  group.add(caption);

  const highlight = new THREE.Mesh(
    new THREE.BoxGeometry(w + 0.22, h + 0.22, 0.04),
    new THREE.MeshBasicMaterial({
      color: C.gold,
      transparent: true,
      opacity: 0,
    })
  );
  highlight.position.z = -0.02;
  group.add(highlight);

  const hit = well;
  hit.userData.goddessId = goddess.id;

  return { group, hit, highlight, id: goddess.id };
}

function createKalash() {
  const g = new THREE.Group();
  const base = new THREE.Mesh(new THREE.SphereGeometry(0.12, 16, 12), goldMat());
  g.add(base);
  const neck = new THREE.Mesh(new THREE.CylinderGeometry(0.04, 0.07, 0.12, 12), goldMat());
  neck.position.y = 0.12;
  g.add(neck);
  const top = new THREE.Mesh(new THREE.ConeGeometry(0.05, 0.12, 10), goldMat());
  top.position.y = 0.22;
  g.add(top);
  return g;
}

function createDiyas() {
  const g = new THREE.Group();
  const chain = new THREE.Mesh(
    new THREE.CylinderGeometry(0.015, 0.015, 0.55, 8),
    goldMat()
  );
  chain.position.y = -0.15;
  g.add(chain);
  const bowl = new THREE.Mesh(
    new THREE.SphereGeometry(0.12, 16, 12, 0, Math.PI * 2, 0, Math.PI / 2),
    goldMat()
  );
  bowl.position.y = -0.42;
  g.add(bowl);
  const flame = new THREE.Mesh(
    new THREE.ConeGeometry(0.04, 0.14, 8),
    new THREE.MeshBasicMaterial({ color: 0xffe08a })
  );
  flame.position.y = -0.32;
  g.add(flame);
  return g;
}

function addMarigoldSwags(wall) {
  const geo = new THREE.SphereGeometry(0.05, 8, 8);
  const mats = [mat(0xf59e0b), mat(0xea580c), mat(0xfacc15)];
  for (let s = 0; s < 3; s += 1) {
    const start = -3.8 + s * 2.6;
    for (let i = 0; i < 18; i += 1) {
      const t = i / 17;
      const bead = new THREE.Mesh(geo, mats[i % 3]);
      bead.position.set(
        start + t * 2.4,
        2.55 - Math.sin(t * Math.PI) * 0.55,
        0.55
      );
      wall.add(bead);
    }
  }
}

function addLotusFrieze(wall, x, y, z) {
  const g = new THREE.Group();
  g.position.set(x, y, z);
  for (let i = -10; i <= 10; i += 1) {
    const petal = new THREE.Mesh(
      new THREE.SphereGeometry(0.13, 8, 6),
      mat(i % 2 === 0 ? 0xf2d2a0 : 0xde6b8a)
    );
    petal.scale.set(1, 0.45, 0.7);
    petal.position.x = i * 0.28;
    g.add(petal);
  }
  wall.add(g);
}

function createLotusTank() {
  const g = new THREE.Group();
  const pool = new THREE.Mesh(
    new THREE.CylinderGeometry(1.35, 1.45, 0.12, 32),
    new THREE.MeshStandardMaterial({
      color: 0x1b6b6b,
      roughness: 0.15,
      metalness: 0.35,
      transparent: true,
      opacity: 0.85,
    })
  );
  g.add(pool);
  const rim = new THREE.Mesh(
    new THREE.TorusGeometry(1.4, 0.06, 8, 40),
    goldMat()
  );
  rim.rotation.x = Math.PI / 2;
  rim.position.y = 0.04;
  g.add(rim);
  for (let i = 0; i < 8; i += 1) {
    const petal = new THREE.Mesh(
      new THREE.SphereGeometry(0.28, 10, 8),
      mat(0xf4c6d7)
    );
    petal.scale.set(1.4, 0.18, 0.7);
    const a = (i / 8) * Math.PI * 2;
    petal.position.set(Math.cos(a) * 0.55, 0.1, Math.sin(a) * 0.55);
    petal.rotation.y = a;
    g.add(petal);
  }
  return g;
}

function makeLabel(text, width, height) {
  const canvas = document.createElement("canvas");
  canvas.width = 1024;
  canvas.height = 128;
  const ctx = canvas.getContext("2d");
  ctx.fillStyle = "#6b1a16";
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.strokeStyle = "#e8c547";
  ctx.lineWidth = 8;
  ctx.strokeRect(6, 6, canvas.width - 12, canvas.height - 12);
  ctx.fillStyle = "#fff4d4";
  ctx.font = "600 48px Cinzel, serif";
  ctx.textAlign = "center";
  ctx.textBaseline = "middle";
  ctx.fillText(text, canvas.width / 2, canvas.height / 2);
  const tex = new THREE.CanvasTexture(canvas);
  tex.anisotropy = 8;
  const mesh = new THREE.Mesh(
    new THREE.PlaneGeometry(width, height),
    new THREE.MeshBasicMaterial({ map: tex, transparent: true })
  );
  return mesh;
}

export function createRoom() {
  const g = new THREE.Group();
  const floor = new THREE.Mesh(
    new THREE.PlaneGeometry(24, 18),
    mat(0x3a2416, { roughness: 0.85 })
  );
  floor.rotation.x = -Math.PI / 2;
  g.add(floor);

  const rug = new THREE.Mesh(
    new THREE.PlaneGeometry(8, 6),
    mat(0x7a1c1c, { roughness: 0.9 })
  );
  rug.rotation.x = -Math.PI / 2;
  rug.position.set(0, 0.01, 3.2);
  g.add(rug);

  const backWall = new THREE.Mesh(
    new THREE.PlaneGeometry(24, 12),
    mat(0x24140f)
  );
  backWall.position.set(0, 6, -1.6);
  g.add(backWall);

  const left = new THREE.Mesh(new THREE.PlaneGeometry(18, 12), mat(0x2a1810));
  left.rotation.y = Math.PI / 2;
  left.position.set(-12, 6, 7);
  g.add(left);

  const right = left.clone();
  right.position.x = 12;
  right.rotation.y = -Math.PI / 2;
  g.add(right);

  return g;
}
