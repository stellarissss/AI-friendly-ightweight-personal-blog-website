/* ============================================================
   stellaris's BLOG — Interactive Node-Graph Background
   可拖拽节点图 + 终端方块光标
   ============================================================ */
(function () {
  'use strict';

  var canvas = document.getElementById('bg-canvas');
  if (!canvas) return;
  var ctx = canvas.getContext('2d');

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var isTouch = window.matchMedia('(hover: none), (pointer: coarse)').matches;

  var DPR = Math.min(window.devicePixelRatio || 1, 2);
  var W = 0, H = 0;

  function resize() {
    W = window.innerWidth;
    H = window.innerHeight;
    canvas.width = W * DPR;
    canvas.height = H * DPR;
    canvas.style.width = W + 'px';
    canvas.style.height = H + 'px';
    ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
  }
  resize();
  window.addEventListener('resize', resize);

  // ---------- 节点 ----------
  var MAX_NODES = W < 640 ? 42 : 90;
  var INIT_NODES = W < 640 ? 26 : 58;
  var LINK_DIST = W < 640 ? 110 : 150;
  var DRAG_RADIUS = 55;

  var nodes = [];
  function makeNode(x, y) {
    return {
      x: x !== undefined ? x : Math.random() * W,
      y: y !== undefined ? y : Math.random() * H,
      vx: (Math.random() - 0.5) * (reduceMotion ? 0 : 0.25),
      vy: (Math.random() - 0.5) * (reduceMotion ? 0 : 0.25),
      r: 1.2 + Math.random() * 1.6
    };
  }
  for (var i = 0; i < INIT_NODES; i++) nodes.push(makeNode());

  // ---------- 指针 ----------
  var pointer = { x: -9999, y: -9999, active: false };
  var dragging = null;
  var dragOffset = { x: 0, y: 0 };

  function isInteractive(target) {
    if (!target) return false;
    var t = target.tagName;
    if (t === 'A' || t === 'BUTTON' || t === 'INPUT' || t === 'TEXTAREA' || t === 'SELECT') return true;
    if (target.closest && target.closest('.gb-form,.md-toolbar,.md-editor-container,.md-action-buttons,.comment-box,.actions,#cmd-input,.terminal')) return true;
    return false;
  }

  function nearestNode(x, y) {
    var best = null, bestD = DRAG_RADIUS;
    for (var i = 0; i < nodes.length; i++) {
      var dx = nodes[i].x - x, dy = nodes[i].y - y;
      var d = Math.sqrt(dx * dx + dy * dy);
      if (d < bestD) { bestD = d; best = nodes[i]; }
    }
    return best;
  }

  document.addEventListener('pointerdown', function (e) {
    if (isInteractive(e.target)) return;
    pointer.active = true;
    var n = nearestNode(e.clientX, e.clientY);
    if (n) {
      dragging = n;
      dragOffset.x = n.x - e.clientX;
      dragOffset.y = n.y - e.clientY;
      canvas.style.cursor = 'grabbing';
    } else {
      // 空白处生成新节点
      nodes.push(makeNode(e.clientX, e.clientY));
      if (nodes.length > MAX_NODES) nodes.shift();
    }
  });

  document.addEventListener('pointermove', function (e) {
    pointer.x = e.clientX;
    pointer.y = e.clientY;
    if (dragging) {
      dragging.x = e.clientX + dragOffset.x;
      dragging.y = e.clientY + dragOffset.y;
      dragging.vx = 0; dragging.vy = 0;
    }
  });

  function endDrag() {
    dragging = null;
    pointer.active = false;
    canvas.style.cursor = '';
  }
  document.addEventListener('pointerup', endDrag);
  document.addEventListener('pointercancel', endDrag);
  document.addEventListener('pointerleave', endDrag);

  // ---------- 渲染 ----------
  function step() {
    if (!reduceMotion) {
      for (var i = 0; i < nodes.length; i++) {
        var n = nodes[i];
        if (n !== dragging) {
          n.x += n.vx;
          n.y += n.vy;
          if (n.x < 0 || n.x > W) n.vx *= -1;
          if (n.y < 0 || n.y > H) n.vy *= -1;
          n.x = Math.max(0, Math.min(W, n.x));
          n.y = Math.max(0, Math.min(H, n.y));
        }
      }
    }

    ctx.clearRect(0, 0, W, H);

    // 连线
    for (var a = 0; a < nodes.length; a++) {
      for (var b = a + 1; b < nodes.length; b++) {
        var dx = nodes[a].x - nodes[b].x;
        var dy = nodes[a].y - nodes[b].y;
        var d = Math.sqrt(dx * dx + dy * dy);
        if (d < LINK_DIST) {
          var alpha = (1 - d / LINK_DIST) * 0.22;
          // 靠近指针的连线更亮
          var midx = (nodes[a].x + nodes[b].x) / 2;
          var midy = (nodes[a].y + nodes[b].y) / 2;
          var pd = Math.sqrt((midx - pointer.x) ** 2 + (midy - pointer.y) ** 2);
          if (pd < 180) alpha += (1 - pd / 180) * 0.35;
          ctx.strokeStyle = 'rgba(0,255,140,' + alpha.toFixed(3) + ')';
          ctx.lineWidth = 1;
          ctx.beginPath();
          ctx.moveTo(nodes[a].x, nodes[a].y);
          ctx.lineTo(nodes[b].x, nodes[b].y);
          ctx.stroke();
        }
      }
    }

    // 节点
    for (var k = 0; k < nodes.length; k++) {
      var nd = nodes[k];
      var glow = 0.5;
      var pd2 = Math.sqrt((nd.x - pointer.x) ** 2 + (nd.y - pointer.y) ** 2);
      if (pd2 < 160) glow = 0.5 + (1 - pd2 / 160) * 0.5;
      if (nd === dragging) glow = 1;
      ctx.fillStyle = 'rgba(0,255,140,' + glow.toFixed(3) + ')';
      ctx.beginPath();
      ctx.arc(nd.x, nd.y, nd.r + (nd === dragging ? 1.5 : 0), 0, Math.PI * 2);
      ctx.fill();
    }

    requestAnimationFrame(step);
  }
  step();

  // ---------- 自定义终端光标 ----------
  if (isTouch) return;
  var cursor = document.getElementById('cursor-block');
  if (!cursor) {
    cursor = document.createElement('div');
    cursor.id = 'cursor-block';
    cursor.className = 'cursor-block';
    document.body.appendChild(cursor);
  }
  var cx = -100, cy = -100, tx = -100, ty = -100;
  window.addEventListener('mousemove', function (e) {
    tx = e.clientX;
    ty = e.clientY;
    var t = e.target;
    cursor.classList.toggle('hide', isInteractive(t));
  });
  (function follow() {
    cx += (tx - cx) * 0.22;
    cy += (ty - cy) * 0.22;
    cursor.style.transform = 'translate(' + cx + 'px,' + cy + 'px)';
    requestAnimationFrame(follow);
  })();
  document.addEventListener('mouseleave', function () { cursor.classList.add('hide'); });
  document.addEventListener('mouseenter', function () { cursor.classList.remove('hide'); });
})();
