import { GODDESSES } from "./goddesses.js";
import { drawBannerCanvas } from "./makhar.js";

const loadImg = (url) =>
  new Promise((resolve) => {
    const img = new Image();
    img.onload = () => resolve(img);
    img.src = url;
  });

const images = {
  bannerBg: await loadImg("/assets/flex-banner-background.png"),
};
await Promise.all(
  GODDESSES.map(async (g) => {
    images[g.id] = await loadImg(g.image);
  })
);

const canvas = drawBannerCanvas(images, 2400, 2200);
canvas.id = "banner";
document.body.appendChild(canvas);
