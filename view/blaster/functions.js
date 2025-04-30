const canvas = document.createElement("canvas");
canvas.height = 1;
canvas.width = 1;

function colorize() {
	canvas.getContext("2d").drawImage(img, 0, 0, 1, 1);
	color = canvas.getContext("2d").getImageData(0, 0, 1, 1);
	document.documentElement.style.setProperty("--accent-color", `rgb(${color.data[0]}, ${color.data[1]}, ${color.data[2]})`);
}

img = new Image();
img.addEventListener("load", colorize);
img.src = document.querySelector(`#current img`).src;
img.crossOrigin = "anonymous";
colorize();
