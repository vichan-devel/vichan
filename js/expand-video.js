/* This file is dedicated to the public domain; you may do as you wish with it. */
/* Note: This code expects the global variable configRoot to be set. */

if (typeof _ == 'undefined') {
	var _ = function(a) {
		return a;
	};
}

function setupVideo(thumb, url) {
	if (thumb.videoAlreadySetUp) {
		return;
	}
	thumb.videoAlreadySetUp = true;

	let video = null;
	let videoContainer, videoHide;
	let expanded = false;
	let hovering = false;
	let loop = true;
	let loopControls = [document.createElement("span"), document.createElement("span")];
	let fileInfo = thumb.parentNode.querySelector(".fileinfo");
	let mouseDown = false;

	function unexpand() {
		if (expanded) {
			expanded = false;
			if (video.pause) {
				video.pause();
			}
			videoContainer.style.display = "none";
			thumb.style.display = "inline";
			video.style.maxWidth = "inherit";
			video.style.maxHeight = "inherit";
		}
	}

	function unhover() {
		if (hovering) {
			hovering = false;
			if (video.pause) {
				video.pause();
			}
			videoContainer.style.display = "none";
			video.style.maxWidth = "inherit";
			video.style.maxHeight = "inherit";
		}
	}

	// Create video element if does not exist yet
	function getVideo() {
		if (video == null) {
			video = document.createElement("video");
			video.src = url;
			video.loop = loop;
			video.innerText = _("Your browser does not support HTML5 video.");

			videoHide = document.createElement("img");
			videoHide.src = configRoot + "static/collapse.gif";
			videoHide.alt = "[ - ]";
			videoHide.title = "Collapse video";
			videoHide.style.marginLeft = "-15px";
			videoHide.style.cssFloat = "left";
			videoHide.addEventListener("click", unexpand, false);

			videoContainer = document.createElement("div");
			videoContainer.style.paddingLeft = "15px";
			videoContainer.style.display = "none";
			videoContainer.appendChild(videoHide);
			videoContainer.appendChild(video);
			thumb.parentNode.insertBefore(videoContainer, thumb.nextSibling);

			// Dragging to the left collapses the video
			video.addEventListener("mousedown", function(e) {
				if (e.button == 0) mouseDown = true;
			}, false);
			video.addEventListener("mouseup", function(e) {
				if (e.button == 0) mouseDown = false;
			}, false);
			video.addEventListener("mouseenter", function(e) {
				mouseDown = false;
			}, false);
			video.addEventListener("mouseout", function(e) {
				if (mouseDown && e.clientX - video.getBoundingClientRect().left <= 0) {
					unexpand();
				}
				mouseDown = false;
			}, false);
		}
	}

	function setVolume() {
		const volume = setting("videovolume");
		video.volume = volume;
		video.muted = (volume === 0);
	}

	// Clicking on thumbnail expands video
	thumb.addEventListener("click", function(e) {
		if (setting("videoexpand") && !e.shiftKey && !e.ctrlKey && !e.altKey && !e.metaKey) {
			getVideo();
			expanded = true;
			hovering = false;

			video.style.position = "static";
			video.style.pointerEvents = "inherit";
			video.style.display = "inline";
			videoHide.style.display = "inline";
			videoContainer.style.display = "block";
			videoContainer.style.position = "static";
			video.parentNode.parentNode.removeAttribute('style');
			thumb.style.display = "none";

			setVolume();
			video.controls = true;
			if (video.readyState == 0) {
				video.addEventListener("loadedmetadata", expand2, false);
			} else {
				setTimeout(expand2, 0);
			}
			let promise = video.play();
			if (promise !== undefined) {
				promise.then(_ => {
				}).catch(_ => {
					video.muted = true;
					video.play();
				});
			}
			e.preventDefault();
		}
	}, false);

	function expand2() {
		video.style.maxWidth = "100%";
		video.style.maxHeight = window.innerHeight + "px";
		var bottom = video.getBoundingClientRect().bottom;
		if (bottom > window.innerHeight) {
			window.scrollBy(0, bottom - window.innerHeight);
		}
		// work around Firefox volume control bug
		video.volume = Math.max(setting("videovolume") - 0.001, 0);
		video.volume = setting("videovolume");
	}

	// Hovering over thumbnail displays video
	thumb.addEventListener("mouseover", function(e) {
		if (setting("videohover")) {
			getVideo();
			expanded = false;
			hovering = true;

			let docRight = document.documentElement.getBoundingClientRect().right;
			let thumbRight = thumb.querySelector("img, video").getBoundingClientRect().right;
			let maxWidth = docRight - thumbRight - 20;
			if (maxWidth < 250) {
				maxWidth = 250;
			}

			video.style.position = "fixed";
			video.style.right = "0px";
			video.style.top = "0px";
			docRight = document.documentElement.getBoundingClientRect().right;
			thumbRight = thumb.querySelector("img, video").getBoundingClientRect().right;
			video.style.maxWidth = maxWidth + "px";
			video.style.maxHeight = "100%";
			video.style.pointerEvents = "none";

			video.style.display = "inline";
			videoHide.style.display = "none";
			videoContainer.style.display = "inline";
			videoContainer.style.position = "fixed";

			setVolume();
			video.controls = false;
			let promise = video.play();
			if (promise !== undefined) {
				promise.then(_ => {
				}).catch(_ => {
					video.muted = true;
					video.play();
				});
			}
		}
	}, false);

	thumb.addEventListener("mouseout", unhover, false);

	// Scroll wheel on thumbnail adjusts default volume
	thumb.addEventListener("wheel", function(e) {
		if (setting("videohover")) {
			var volume = setting("videovolume");
			if (e.deltaY > 0) {
				volume -= 0.1;
			}
			if (e.deltaY < 0) {
				volume += 0.1;
			}
			if (volume < 0) {
				volume = 0;
			}
			if (volume > 1) {
				volume = 1;
			}
			if (video != null) {
				video.muted = (volume == 0);
				video.volume = volume;
			}
			changeSetting("videovolume", volume);
			e.preventDefault();
		}
	}, false);

	// [play once] vs [loop] controls
	function setupLoopControl(i) {
		loopControls[i].addEventListener("click", function(e) {
			loop = (i != 0);
			thumb.href = thumb.href.replace(/([\?&])loop=\d+/, "$1loop=" + i);
			if (video != null) {
				video.loop = loop;
				if (loop && video.currentTime >= video.duration) {
					video.currentTime = 0;
				}
			}
			loopControls[i].style.fontWeight = "bold";
			loopControls[1-i].style.fontWeight = "inherit";
		}, false);
	}

	loopControls[0].textContent = _("[play once]");
	loopControls[1].textContent = _("[loop]");
	loopControls[1].style.fontWeight = "bold";
	for (var i = 0; i < 2; i++) {
		setupLoopControl(i);
		loopControls[i].style.whiteSpace = "nowrap";
		fileInfo.appendChild(document.createTextNode(" "));
		fileInfo.appendChild(loopControls[i]);
	}
}

function setupVideosIn(element) {
	let thumbs = element.querySelectorAll("a.file");
	for (let i = 0; i < thumbs.length; i++) {
		if (/\.webm$|\.mp4$/.test(thumbs[i].pathname)) {
			setupVideo(thumbs[i], thumbs[i].href);
		} else {
			let m = thumbs[i].search.match(/\bv=([^&]*)/);
			if (m != null) {
				let url = decodeURIComponent(m[1]);
				if (/\.webm$|\.mp4$/.test(url)) {
					setupVideo(thumbs[i], url);
				}
			}
		}
	}
}

onReady(function(){
	// Insert menu from settings.js
	if (typeof settingsMenu != "undefined" && typeof Options == "undefined") {
		document.body.insertBefore(settingsMenu, document.getElementsByTagName("hr")[0]);
	}

	// Setup Javascript events for videos in document now
	setupVideosIn(document);

	// Setup Javascript events for videos added by updater
	if (window.MutationObserver) {
		let observer = new MutationObserver(function(mutations) {
			for (let i = 0; i < mutations.length; i++) {
				let additions = mutations[i].addedNodes;
				if (additions == null) {
					continue;
				}
				for (let j = 0; j < additions.length; j++) {
					let node = additions[j];
					if (node.nodeType == 1) {
						setupVideosIn(node);
					}
				}
			}
		});
		observer.observe(document.body, {childList: true, subtree: true});
	}
});
