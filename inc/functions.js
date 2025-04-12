sync();

setInterval(update, 1000);
setInterval(sync, 10000);

let progress = {
    position: 0,
    duration: 0,
    updated: Date.now(),
    track: ""
};

function format_duration(ms) {

    let totalSeconds = Math.floor(ms / 1000);
    let minutes = Math.floor(totalSeconds / 60);
    let seconds = totalSeconds % 60;
    
    return `${minutes} min ${seconds} s`;
    
}

function update() {
    
    let position = progress.position + (Date.now() - progress.updated);

    if (position > progress.duration) {

        position = progress.duration;
        
        location.reload();
        
    }

    document.querySelector(".position span").textContent = format_duration(position);
    document.querySelector(".progress div").style.width = ((position / progress.duration) * 100) + "%";

}

function sync() {

    let reload = false;

    fetch("inc/np.php").then(res => res.json()).then(data => {

        if (data.position > data.duration) {

            reload = true;

        }

        if (progress.track === "") {

            progress.track = data.track.uri;

        }

        if (progress.track !== data.track.uri) {

            reload = true;

        }

        if (reload) {

            location.reload();

            return;

        }

        progress.position = data.position;
        progress.duration = data.duration;
        progress.updated = Date.now();

    }).catch(err => console.error("Sync error:", err));

}