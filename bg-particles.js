(function() {
  const canvas = document.getElementById('particle-canvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  let particles = [];
  let mouse = { x: null, y: null, radius: 150 };
  let animationId;
  let isMobile = window.innerWidth < 768;

  const config = {
    particleCount: isMobile ? 50 : 100,
    maxDistance: isMobile ? 120 : 150,
    particleSize: 2,
    lineWidth: 0.8,
    speed: 0.5,
    mouseForce: 0.03,
    colors: {
      particle: '#00fff9',
      line: '#00fff9',
      accent: '#ff00c1'
    }
  };

  function resize() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    isMobile = window.innerWidth < 768;
    config.particleCount = isMobile ? 50 : 100;
    config.maxDistance = isMobile ? 120 : 150;
    initParticles();
  }

  class Particle {
    constructor() {
      this.x = Math.random() * canvas.width;
      this.y = Math.random() * canvas.height;
      this.vx = (Math.random() - 0.5) * config.speed;
      this.vy = (Math.random() - 0.5) * config.speed;
      this.size = Math.random() * config.particleSize + 1;
      this.baseSize = this.size;
      this.hue = Math.random() * 60 + 170;
    }

    update() {
      if (mouse.x !== null && mouse.y !== null) {
        const dx = mouse.x - this.x;
        const dy = mouse.y - this.y;
        const dist = Math.sqrt(dx * dx + dy * dy);

        if (dist < mouse.radius) {
          const force = (mouse.radius - dist) / mouse.radius;
          const angle = Math.atan2(dy, dx);
          this.vx += Math.cos(angle) * force * config.mouseForce;
          this.vy += Math.sin(angle) * force * config.mouseForce;
          this.size = this.baseSize + force * 3;
          this.hue = 300 + force * 60;
        } else {
          this.size += (this.baseSize - this.size) * 0.05;
        }
      }

      this.vx *= 0.99;
      this.vy *= 0.99;

      const speed = Math.sqrt(this.vx * this.vx + this.vy * this.vy);
      if (speed > config.speed * 3) {
        this.vx = (this.vx / speed) * config.speed * 3;
        this.vy = (this.vy / speed) * config.speed * 3;
      }

      this.x += this.vx;
      this.y += this.vy;

      if (this.x < 0 || this.x > canvas.width) this.vx *= -1;
      if (this.y < 0 || this.y > canvas.height) this.vy *= -1;

      this.x = Math.max(0, Math.min(canvas.width, this.x));
      this.y = Math.max(0, Math.min(canvas.height, this.y));
    }

    draw() {
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
      ctx.fillStyle = `hsla(${this.hue}, 100%, 60%, 0.8)`;
      ctx.shadowBlur = 15;
      ctx.shadowColor = `hsla(${this.hue}, 100%, 60%, 0.5)`;
      ctx.fill();
      ctx.shadowBlur = 0;
    }
  }

  function initParticles() {
    particles = [];
    for (let i = 0; i < config.particleCount; i++) {
      particles.push(new Particle());
    }
  }

  function drawLines() {
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx = particles[i].x - particles[j].x;
        const dy = particles[i].y - particles[j].y;
        const dist = Math.sqrt(dx * dx + dy * dy);

        if (dist < config.maxDistance) {
          const opacity = (1 - dist / config.maxDistance) * 0.3;
          const hue = (particles[i].hue + particles[j].hue) / 2;

          ctx.beginPath();
          ctx.strokeStyle = `hsla(${hue}, 100%, 60%, ${opacity})`;
          ctx.lineWidth = config.lineWidth;
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          ctx.stroke();
        }
      }

      if (mouse.x !== null && mouse.y !== null) {
        const dx = particles[i].x - mouse.x;
        const dy = particles[i].y - mouse.y;
        const dist = Math.sqrt(dx * dx + dy * dy);

        if (dist < mouse.radius) {
          const opacity = (1 - dist / mouse.radius) * 0.5;
          ctx.beginPath();
          ctx.strokeStyle = `hsla(${particles[i].hue}, 100%, 70%, ${opacity})`;
          ctx.lineWidth = 1;
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(mouse.x, mouse.y);
          ctx.stroke();
        }
      }
    }
  }

  function animate() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    particles.forEach(p => {
      p.update();
    });

    drawLines();

    particles.forEach(p => {
      p.draw();
    });

    animationId = requestAnimationFrame(animate);
  }

  function createExplosion(x, y) {
    const count = 20;
    for (let i = 0; i < count; i++) {
      const p = new Particle();
      p.x = x;
      p.y = y;
      const angle = (Math.PI * 2 * i) / count + Math.random() * 0.5;
      const speed = 2 + Math.random() * 3;
      p.vx = Math.cos(angle) * speed;
      p.vy = Math.sin(angle) * speed;
      p.size = 3 + Math.random() * 3;
      p.baseSize = p.size;
      p.hue = Math.random() * 120 + 270;
      particles.push(p);
    }

    if (particles.length > config.particleCount + 50) {
      particles.splice(0, particles.length - config.particleCount - 50);
    }
  }

  window.addEventListener('resize', () => {
    cancelAnimationFrame(animationId);
    resize();
    animate();
  });

  window.addEventListener('mousemove', (e) => {
    mouse.x = e.clientX;
    mouse.y = e.clientY;
  });

  window.addEventListener('mouseout', () => {
    mouse.x = null;
    mouse.y = null;
  });

  window.addEventListener('click', (e) => {
    createExplosion(e.clientX, e.clientY);
  });

  window.addEventListener('touchmove', (e) => {
    if (e.touches.length > 0) {
      mouse.x = e.touches[0].clientX;
      mouse.y = e.touches[0].clientY;
    }
  }, { passive: true });

  window.addEventListener('touchstart', (e) => {
    if (e.touches.length > 0) {
      mouse.x = e.touches[0].clientX;
      mouse.y = e.touches[0].clientY;
      createExplosion(e.touches[0].clientX, e.touches[0].clientY);
    }
  }, { passive: true });

  window.addEventListener('touchend', () => {
    mouse.x = null;
    mouse.y = null;
  });

  resize();
  animate();
})();
