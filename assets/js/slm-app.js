document.addEventListener("DOMContentLoaded", function () {
    const particlesContainer = document.getElementById("particles-js");
    const backgroundLayer = document.getElementById("slm-space-bg");

    if (!particlesContainer || !backgroundLayer) {
        return;
    }

    if (typeof particlesJS !== "undefined") {
        particlesJS("particles-js", {
            particles: {
                number: {
                    value: 520,
                    density: {
                        enable: true,
                        value_area: 1200
                    }
                },
                color: {
                    value: ["#ffffff", "#dbeafe", "#c7d2fe", "#bfdbfe"]
                },
                shape: {
                    type: "circle"
                },
                opacity: {
                    value: 0.85,
                    random: true,
                    anim: {
                        enable: true,
                        speed: 0.2,
                        opacity_min: 0.35,
                        sync: false
                    }
                },
                size: {
                    value: 2.2,
                    random: true,
                    anim: {
                        enable: true,
                        speed: 0.8,
                        size_min: 0.2,
                        sync: false
                    }
                },
                line_linked: {
                    enable: false
                },
                move: {
                    enable: true,
                    speed: 0.45,
                    direction: "none",
                    random: true,
                    straight: false,
                    out_mode: "out",
                    bounce: false,
                    attract: {
                        enable: false
                    }
                }
            },
            interactivity: {
                detect_on: "window",
                events: {
                    onhover: {
                        enable: true,
                        mode: "repulse"
                    },
                    onclick: {
                        enable: false
                    },
                    resize: true
                },
                modes: {
                    repulse: {
                        distance: 180,
                        duration: 0.7,
                        speed: 1.2
                    }
                }
            },
            retina_detect: true
        });
    }

    function createShootingStar() {
        const star = document.createElement("div");
        star.classList.add("shooting-star");

        star.style.top = Math.random() * 45 + "vh";
        star.style.left = Math.random() * 55 + "vw";
        star.style.animationDuration = (2.4 + Math.random() * 2.4) + "s";

        backgroundLayer.appendChild(star);

        setTimeout(function () {
            if (star.parentNode) {
                star.parentNode.removeChild(star);
            }
        }, 5200);
    }

    setInterval(createShootingStar, 3200);
});
document.addEventListener("DOMContentLoaded", function(){

let search = document.getElementById("slm-search");

if(!search) return;

search.addEventListener("keyup", function(){

let value = this.value.toLowerCase();
let items = document.querySelectorAll(".slm-card-sm");

items.forEach(function(card){

let text = card.innerText.toLowerCase();

if(text.indexOf(value) > -1){
card.style.display = "";
}else{
card.style.display = "none";
}

});

});

});