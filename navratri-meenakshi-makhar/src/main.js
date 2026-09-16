import "./style.css";
import * as THREE from "three";
import { OrbitControls } from "three/addons/controls/OrbitControls.js";
import { GODDESSES } from "./goddesses.js";
import { createMakhar, createRoom } from "./makhar.js";

const loader = new THREE.TextureLoader();
const loadTex = (url) =>
  new Promise((resolve) => {
    loader.load(url, (t) => {
      t.colorSpace = THREE.SRGBColorSpace;
      t.anisotropy = 8;
      resolve(t);
    });
  });

const textures = {
  gopuram: await loadTex("/assets/gopuram-sculpture-texture.png"),
};
textures.gopuram.wrapS = textures.gopuram.wrapT = THREE.RepeatWrapping;
textures.gopuram.repeat.set(2.4, 1);

await Promise.all(
  GODDESSES.map(async (g) => {
    textures[g.id] = await loadTex(g.image);
  })
);

const app = document.querySelector("#app");
const scene = new THREE.Scene();
scene.background = new THREE.Color(0x100a07);
scene.fog = new THREE.Fog(0x100a07, 22, 42);

const camera = new THREE.PerspectiveCamera(42, innerWidth / innerHeight, 0.1, 80);
camera.position.set(0, 4.8, 15.2);

const renderer = new THREE.WebGLRenderer({ antialias: true });
renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
renderer.setSize(innerWidth, innerHeight);
renderer.toneMapping = THREE.ACESFilmicToneMapping;
renderer.toneMappingExposure = 1.12;
renderer.shadowMap.enabled = false;
app.appendChild(renderer.domElement);

const controls = new OrbitControls(camera, renderer.domElement);
controls.enableDamping = true;
controls.target.set(0, 4.2, 0);
controls.maxPolarAngle = Math.PI * 0.49;
controls.minDistance = 6;
controls.maxDistance = 22;

scene.add(createRoom());
const { root, niches, lampLights } = createMakhar(textures);
scene.add(root);

const hemi = new THREE.HemisphereLight(0xffe6b8, 0x3a1c10, 0.85);
scene.add(hemi);
const key = new THREE.DirectionalLight(0xfff1d0, 1.35);
key.position.set(4, 10, 8);
scene.add(key);
const fill = new THREE.DirectionalLight(0x88c0c8, 0.35);
fill.position.set(-6, 6, 4);
scene.add(fill);
const shrineLight = new THREE.PointLight(0xffb367, 14, 8, 1.4);
shrineLight.position.set(0, 4.2, 1.4);
scene.add(shrineLight);

const raycaster = new THREE.Raycaster();
const pointer = new THREE.Vector2();
let selected = GODDESSES[4];

const panel = document.querySelector("#goddess-panel");
const legend = document.querySelector("#legend-list");

function renderPanel(g) {
  selected = g;
  panel.innerHTML = `
    <p class="kicker">Navarātri · Day ${g.day}</p>
    <h2>${g.name}</h2>
    <div class="meta">
      <span class="chip">${g.title}</span>
      <span class="chip">Vāhana: ${g.vahana}</span>
    </div>
    <img class="portrait" src="${g.image}" alt="${g.name}" />
    <p>${g.note}</p>
  `;
  legend.querySelectorAll("li").forEach((li) => {
    li.classList.toggle("active", li.dataset.id === g.id);
  });
  niches.forEach((n) => {
    n.highlight.material.opacity = n.id === g.id ? 0.55 : 0;
  });
}

legend.innerHTML = GODDESSES.map(
  (g) => `<li data-id="${g.id}">${g.day}. ${g.name}</li>`
).join("");
legend.addEventListener("click", (e) => {
  const li = e.target.closest("li");
  if (!li) return;
  const g = GODDESSES.find((x) => x.id === li.dataset.id);
  renderPanel(g);
});

renderPanel(selected);

function onPointer(event) {
  const onCanvas = event.target === renderer.domElement;
  if (!onCanvas) {
    if (event.type === "pointermove") document.body.style.cursor = "default";
    return;
  }
  pointer.x = (event.clientX / innerWidth) * 2 - 1;
  pointer.y = -(event.clientY / innerHeight) * 2 + 1;
  raycaster.setFromCamera(pointer, camera);
  const hits = raycaster.intersectObjects(
    niches.map((n) => n.hit),
    false
  );
  document.body.style.cursor = hits.length ? "pointer" : "default";
  if (event.type === "click" && hits[0]) {
    const id = hits[0].object.userData.goddessId;
    const g = GODDESSES.find((x) => x.id === id);
    if (g) renderPanel(g);
  }
}

window.addEventListener("pointermove", onPointer);
window.addEventListener("click", onPointer);

document.querySelector("#btn-front").addEventListener("click", () => {
  camera.position.set(0, 4.8, 15.2);
  controls.target.set(0, 4.2, 0);
});
document.querySelector("#btn-three").addEventListener("click", () => {
  camera.position.set(8.2, 6.0, 11.5);
  controls.target.set(0, 4.2, 0);
});
document.querySelector("#btn-close").addEventListener("click", () => {
  camera.position.set(0, 4.0, 8.4);
  controls.target.set(0, 4.0, 0.4);
});

let lampsOn = true;
document.querySelector("#btn-lamps").addEventListener("click", (e) => {
  lampsOn = !lampsOn;
  e.currentTarget.classList.toggle("active", lampsOn);
  shrineLight.intensity = lampsOn ? 14 : 2.5;
  hemi.intensity = lampsOn ? 0.85 : 0.22;
  key.intensity = lampsOn ? 1.35 : 0.25;
  lampLights.forEach((l) => {
    l.intensity = lampsOn ? 5.5 : 0;
  });
});
document.querySelector("#btn-lamps").classList.add("active");

window.addEventListener("resize", () => {
  camera.aspect = innerWidth / innerHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(innerWidth, innerHeight);
});

renderer.setAnimationLoop(() => {
  const t = performance.now() * 0.001;
  shrineLight.intensity = lampsOn ? 12 + Math.sin(t * 2.2) * 2 : 2.5;
  controls.update();
  renderer.render(scene, camera);
});
