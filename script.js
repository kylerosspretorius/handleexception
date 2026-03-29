// ===================================
// Typewriter effect
// ===================================
const titles = [
    'Senior PHP Developer',
    'DevOps Engineer',
    'AWS Solutions Architect',
    'Technical Lead',
    'Laravel & Symfony Expert'
];

const typewriterEl = document.getElementById('typewriter-text');
let titleIndex  = 0;
let charIndex   = 0;
let isDeleting  = false;

function typeWriter() {
    const current = titles[titleIndex];

    if (isDeleting) {
        typewriterEl.textContent = current.slice(0, --charIndex);
    } else {
        typewriterEl.textContent = current.slice(0, ++charIndex);
    }

    let delay = isDeleting ? 50 : 90;

    if (!isDeleting && charIndex === current.length) {
        delay = 2000;
        isDeleting = true;
    } else if (isDeleting && charIndex === 0) {
        isDeleting = false;
        titleIndex = (titleIndex + 1) % titles.length;
        delay = 400;
    }

    setTimeout(typeWriter, delay);
}

setTimeout(typeWriter, 1200);

// ===================================
// Network particle canvas
// ===================================
(function initParticles() {
    const canvas = document.getElementById('particles-canvas');
    if (!canvas) return;
    const ctx    = canvas.getContext('2d');

    const COUNT    = 70;
    const MAX_DIST = 140;
    const C        = [14, 165, 233];
    let W, H, particles;

    function rand(a, b) { return a + Math.random() * (b - a); }

    function resize() {
        W = canvas.width  = canvas.parentElement.offsetWidth;
        H = canvas.height = canvas.parentElement.offsetHeight;
    }

    function mkParticle() {
        return {
            x: rand(0, W), y: rand(0, H),
            vx: rand(-0.35, 0.35),
            vy: rand(-0.35, 0.35),
            r: rand(1.5, 3.2)
        };
    }

    function init() { resize(); particles = Array.from({ length: COUNT }, mkParticle); }

    function draw() {
        ctx.clearRect(0, 0, W, H);

        // Draw connecting lines between nearby particles
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx   = particles[i].x - particles[j].x;
                const dy   = particles[i].y - particles[j].y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < MAX_DIST) {
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.strokeStyle = `rgba(${C},${(1 - dist / MAX_DIST) * 0.35})`;
                    ctx.lineWidth   = 0.8;
                    ctx.stroke();
                }
            }
        }

        // Draw nodes
        particles.forEach(p => {
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(${C}, 0.7)`;
            ctx.fill();

            p.x += p.vx;
            p.y += p.vy;
            if (p.x < 0 || p.x > W) p.vx *= -1;
            if (p.y < 0 || p.y > H) p.vy *= -1;
        });

        requestAnimationFrame(draw);
    }

    window.addEventListener('resize', init);
    init();
    draw();
})();

// ===================================
// Progress bar
// ===================================
const progressBar = document.getElementById('progress-bar');

// ===================================
// Navbar scroll + active link
// ===================================
const navbar      = document.getElementById('navbar');
const scrollTopBtn = document.getElementById('scroll-top');

window.addEventListener('scroll', () => {
    const scrollTop = window.scrollY;
    const docHeight = document.documentElement.scrollHeight - window.innerHeight;
    if (progressBar) progressBar.style.width = `${(scrollTop / docHeight) * 100}%`;
    navbar.classList.toggle('scrolled', scrollTop > 20);
    scrollTopBtn.classList.toggle('visible', scrollTop > 400);
    updateActiveLink();
});

scrollTopBtn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// ===================================
// Hamburger menu
// ===================================
const hamburger = document.getElementById('hamburger');
const navLinks  = document.getElementById('nav-links');

hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('open');
    navLinks.classList.toggle('open');
});

document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        hamburger.classList.remove('open');
        navLinks.classList.remove('open');
    });
});

// ===================================
// Active nav link on scroll
// ===================================
const sections = document.querySelectorAll('section[id]');
const navItems = document.querySelectorAll('.nav-link');

function updateActiveLink() {
    const scrollPos = window.scrollY + 100;
    sections.forEach(section => {
        const top    = section.offsetTop;
        const height = section.offsetHeight;
        const id     = section.getAttribute('id');
        if (scrollPos >= top && scrollPos < top + height) {
            navItems.forEach(item => {
                item.classList.toggle('active', item.getAttribute('href') === `#${id}`);
            });
        }
    });
}

// ===================================
// Smooth scroll (offset for fixed nav)
// ===================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', e => {
        const href = anchor.getAttribute('href');
        if (href === '#') return;
        const target = document.querySelector(href);
        if (!target) return;
        e.preventDefault();
        const navHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nav-height')) || 70;
        window.scrollTo({ top: target.offsetTop - navHeight, behavior: 'smooth' });
    });
});

// ===================================
// Scroll-reveal (Intersection Observer)
// ===================================
const animatedEls = document.querySelectorAll('.fade-in');

// Hero elements: stagger on page load
document.querySelectorAll('#hero .fade-in').forEach((el, i) => {
    setTimeout(() => el.classList.add('visible'), i * 130);
});

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting && !entry.target.closest('#hero')) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

animatedEls.forEach(el => {
    if (!el.closest('#hero')) observer.observe(el);
});

// ===================================
// Counter animation for About stats
// ===================================
function animateCounter(el) {
    const target = parseInt(el.getAttribute('data-target'));
    if (isNaN(target)) return;
    const duration = 1200;
    const step     = 16;
    const steps    = duration / step;
    let current    = 0;

    const timer = setInterval(() => {
        current++;
        el.textContent = Math.round((current / steps) * target);
        if (current >= steps) {
            el.textContent = target;
            clearInterval(timer);
        }
    }, step);
}

const statsObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.querySelectorAll('[data-target]').forEach(animateCounter);
            statsObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.3 });

const statsSection = document.querySelector('.about-stats');
if (statsSection) statsObserver.observe(statsSection);

// ===================================
// Copy email button
// ===================================
const copyBtn = document.getElementById('copy-email');
if (copyBtn) {
    copyBtn.addEventListener('click', () => {
        navigator.clipboard.writeText('kyleross.pretorius@gmail.com').then(() => {
            const original = copyBtn.innerHTML;
            copyBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Copied!`;
            copyBtn.classList.add('copied');
            setTimeout(() => {
                copyBtn.innerHTML = original;
                copyBtn.classList.remove('copied');
            }, 2500);
        });
    });
}

// ===================================
// Terminal 3D tilt on hover
// ===================================
const terminal = document.querySelector('.hero-terminal');
if (terminal) {
    terminal.addEventListener('mousemove', e => {
        const rect = terminal.getBoundingClientRect();
        const x    = (e.clientX - rect.left) / rect.width  - 0.5;
        const y    = (e.clientY - rect.top)  / rect.height - 0.5;
        terminal.style.transform = `perspective(900px) rotateY(${x * 10}deg) rotateX(${-y * 10}deg) scale(1.02)`;
        terminal.style.boxShadow = `${-x * 20}px ${-y * 20}px 60px rgba(14,165,233,0.1), 0 24px 80px rgba(0,0,0,0.6)`;
    });
    terminal.addEventListener('mouseleave', () => {
        terminal.style.transform = 'perspective(900px) rotateY(0) rotateX(0) scale(1)';
        terminal.style.boxShadow = '';
    });
}
