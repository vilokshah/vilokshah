import "./style.css";
import * as THREE from "three";
import { OrbitControls } from "three/addons/controls/OrbitControls.js";
import { GODDESSES } from "./goddesses.js";
import { composeBannerTexture, createMakhar, createRoom } from "./makhar.js";

const loader = new THREE.TextureLoader();
const loadTex = (url) =>
  new Promise((resolve) => {
    loader.load(url, (t) => {
      t.colorSpace = THREE.SRGBColorSpace;
      t.anisotropy = 8;
      resolve(t);
    });
  });

const loadImg = (url) =>
  new Promise((resolve) => {
    const img = new Image();
    img.crossOrigin = "anonymous";
    img.onload = () => resolve(img);
    img.src = url;
  });

const textures = {};
const images = {
  bannerBg: await loadImg("/assets/flex-banner-background.png"),
};

await Promise.all(
  GODDESSES.map(async (g) => {
    textures[g.id] = await loadTex(g.image);
    images[g.id] = await loadImg(g.image);
  })
);
textures.marbleFront = await loadTex("/assets/white-marble-temple.png");

const bannerMap = composeBannerTexture(images);

const app = document.querySelector("#app");
const scene = new THREE.Scene();
scene.background = new THREE.Color(0x100a07);
scene.fog = new THREE.Fog(0x100a07, 24, 48);

const camera = new THREE.PerspectiveCamera(40, innerWidth / innerHeight, 0.1, 80);
camera.position.set(0, 5.6, 16.5);

const renderer = new THREE.WebGLRenderer({ antialias: true });
renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
renderer.setSize(innerWidth, innerHeight);
renderer.toneMapping = THREE.ACESFilmicToneMapping;
renderer.toneMappingExposure = 1.08;
app.appendChild(renderer.domElement);

const controls = new OrbitControls(camera, renderer.domElement);
controls.enableDamping = true;
controls.target.set(0, 5.2, 0);
controls.maxPolarAngle = Math.PI * 0.49;
controls.minDistance = 6;
controls.maxDistance = 26;

scene.add(createRoom());
const { root, niches, lampLights } = createMakhar(textures, bannerMap);
scene.add(root);

const hemi = new THREE.HemisphereLight(0xffe6b8, 0x3a1c10, 0.9);
scene.add(hemi);
const key = new THREE.DirectionalLight(0xfff1d0, 1.25);
key.position.set(5, 11, 9);
scene.add(key);
const fill = new THREE.DirectionalLight(0x88c0c8, 0.32);
fill.position.set(-6, 7, 4);
scene.add(fill);
const shrineLight = new THREE.PointLight(0xffe6c4, 10, 9, 1.4);
shrineLight.position.set(0, 3.2, 2.4);
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
    n.highlight.material.opacity = n.id === g.id ? 0.35 : 0;
  });
}

legend.innerHTML = GODDESSES.map(
  (g) => `<li data-id="${g.id}">${g.day}. ${g.name}</li>`
).join("");
legend.addEventListener("click", (e) => {
  const li = e.target.closest("li");
  if (!li) return;
  renderPanel(GODDESSES.find((x) => x.id === li.dataset.id));
});

renderPanel(selected);

function onPointer(event) {
  if (event.target !== renderer.domElement) {
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
  camera.position.set(0, 5.6, 16.5);
  controls.target.set(0, 5.2, 0);
});
document.querySelector("#btn-three").addEventListener("click", () => {
  camera.position.set(9, 6.4, 13);
  controls.target.set(0, 5.2, 0);
});
document.querySelector("#btn-close").addEventListener("click", () => {
  camera.position.set(0, 2.2, 8.5);
  controls.target.set(0, 2.0, 0.8);
});

let lampsOn = true;
document.querySelector("#btn-lamps").addEventListener("click", (e) => {
  lampsOn = !lampsOn;
  e.currentTarget.classList.toggle("active", lampsOn);
  shrineLight.intensity = lampsOn ? 10 : 2;
  hemi.intensity = lampsOn ? 0.9 : 0.22;
  key.intensity = lampsOn ? 1.25 : 0.28;
  lampLights.forEach((l) => {
    l.intensity = lampsOn ? 4.2 : 0;
  });
});
document.querySelector("#btn-lamps").classList.add("active");

window.addEventListener("resize", () => {
  camera.aspect = innerWidth / innerHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(innerWidth, innerHeight);
});

renderer.setAnimationLoop(() => {
  controls.update();
  renderer.render(scene, camera);
});
