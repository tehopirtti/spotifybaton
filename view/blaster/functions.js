const canvas = document.createElement("canvas");
canvas.height = 16;
canvas.width = 16;

const img = new Image();

function colorize() {
	canvas.getContext("2d").drawImage(img, 0, 0, canvas.width, canvas.height);
	thumb = canvas.getContext("2d").getImageData(0, 0, canvas.width, canvas.width);
	let r = 0, g = 0, b = 0;
	for (let i = 0; i < thumb.data.length; i++) {
		r += thumb.data[i++];
		g += thumb.data[i++];
		b += thumb.data[i++];
	}
	r = (r / (thumb.data.length * .25));
	g = (g / (thumb.data.length * .25));
	b = (b / (thumb.data.length * .25));
	r = ~~(r < 128 ? r = (r + 128) / 2 : r);
	g = ~~(g < 128 ? g = (g + 128) / 2 : g);
	b = ~~(b < 128 ? b = (b + 128) / 2 : b);
	document.documentElement.style.setProperty("--accent-color", `rgb(${r}, ${g}, ${b})`);
}

img.addEventListener("load", colorize);
img.crossOrigin = "anonymous";
img.src = document.querySelector(`#current img`).src;
