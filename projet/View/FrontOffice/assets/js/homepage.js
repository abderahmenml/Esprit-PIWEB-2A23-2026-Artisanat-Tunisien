const galleryData = [
  {
    title: "Tissage de Nabeul",
    category: "textile",
    size: "wide",
    image: "assets/images/homepage/about.jpg"
  },
  {
    title: "Email ceramique",
    category: "ceramique",
    size: "square",
    image: "assets/images/homepage/products-01.jpg"
  },
  {
    title: "Sculpture bois",
    category: "bois",
    size: "tall",
    image: "assets/images/homepage/intro11.jpg"
  },
  {
    title: "Collection bijoux",
    category: "bijoux",
    size: "square",
    image: "assets/images/homepage/product-03.webp"
  },
  {
    title: "Finitions broderie",
    category: "textile",
    size: "square",
    image: "assets/images/homepage/products-02.jpg"
  },
  {
    title: "Argile locale",
    category: "ceramique",
    size: "wide",
    image: "assets/images/homepage/taswira.jpg"
  },
  {
    title: "MosaIque artisanale",
    category: "ceramique",
    size: "square",
    image: "assets/images/homepage/picture_old.jpg"
  },
  {
    title: "Bois grave",
    category: "bois",
    size: "square",
    image: "assets/images/homepage/bg.png"
  }
];

const projectsData = [
  {
    title: "Capsule kaftan moderne",
    category: "Textile",
    status: "Ouvert",
    budget: "1800 DT",
    text: "Serie limitee orientee export avec motifs revisites et coupe premium.",
    tags: ["broderie", "patronage", "photo produit"],
    image: "assets/images/homepage/intro.jpg"
  },
  {
    title: "Atelier ceramique utilitaire",
    category: "Ceramique",
    status: "En cours",
    budget: "2300 DT",
    text: "Collection vaisselle locale avec email alimentaire et packaging ecoresponsable.",
    tags: ["argile", "email", "branding"],
    image: "assets/images/homepage/products-01.jpg"
  },
  {
    title: "Mobilier bois olive",
    category: "Bois",
    status: "Ouvert",
    budget: "3200 DT",
    text: "Pieces haut de gamme inspirees des formes tunisiennes traditionnelles.",
    tags: ["ebenisterie", "finitions", "livraison"],
    image: "assets/images/homepage/ood.jpg"
  },
  {
    title: "Bijoux cuivre grave",
    category: "Bijouterie",
    status: "Ferme",
    budget: "950 DT",
    text: "Mini collection avec gravure fine et storytelling digital pour reseaux sociaux.",
    tags: ["gravure", "shooting", "vente web"],
    image: "assets/images/homepage/product-03.webp"
  },
  {
    title: "Textile maison naturel",
    category: "Textile",
    status: "En cours",
    budget: "1400 DT",
    text: "Ligne de coussins et nappes avec colorants naturels et finitions manuelles.",
    tags: ["teinture", "tissage", "distribution"],
    image: "assets/images/homepage/taswira.jpg"
  },
  {
    title: "Serie pots design",
    category: "Ceramique",
    status: "Ouvert",
    budget: "1700 DT",
    text: "Edition decorative pour concept stores, avec identite visuelle epuree.",
    tags: ["moulage", "email", "catalogue"],
    image: "assets/images/homepage/about_original.jpg"
  }
];

const galleryGrid = document.getElementById("galleryGrid");
const galleryFilters = document.getElementById("galleryFilters");
const projectGrid = document.getElementById("projectGrid");
const projectEmpty = document.getElementById("projectEmpty");
const searchInput = document.getElementById("projectSearch");
const categorySelect = document.getElementById("projectCategory");
const statusSelect = document.getElementById("projectStatus");

const lightbox = document.getElementById("lightbox");
const lightboxImage = document.getElementById("lightboxImage");
const lightboxCaption = document.getElementById("lightboxCaption");
const lightboxClose = document.getElementById("lightboxClose");

function renderGallery(filter) {
  galleryGrid.innerHTML = "";

  const normalizedFilter = filter || "all";
  const items = galleryData.filter((item) => {
    if (normalizedFilter === "all") {
      return true;
    }
    return item.category === normalizedFilter;
  });

  for (let i = 0; i < items.length; i += 1) {
    const shot = items[i];
    const figure = document.createElement("figure");
    figure.className = "shot " + shot.size;
    figure.dataset.title = shot.title;
    figure.dataset.image = shot.image;

    figure.innerHTML =
      '<img src="' + shot.image + '" alt="' + shot.title + '">' +
      "<figcaption>" + shot.title + "</figcaption>";

    figure.addEventListener("click", () => {
      openLightbox(shot.image, shot.title);
    });

    galleryGrid.appendChild(figure);
  }
}

function statusClass(status) {
  if (status === "Ouvert") {
    return "open";
  }
  if (status === "En cours") {
    return "progress";
  }
  return "closed";
}

function renderProjects() {
  const query = searchInput.value.trim().toLowerCase();
  const category = categorySelect.value;
  const status = statusSelect.value;

  projectGrid.innerHTML = "";

  const filtered = projectsData.filter((project) => {
    const joinText =
      (project.title + " " + project.text + " " + project.tags.join(" ")).toLowerCase();

    const matchesQuery = query === "" || joinText.includes(query);
    const matchesCategory = category === "all" || project.category === category;
    const matchesStatus = status === "all" || project.status === status;

    return matchesQuery && matchesCategory && matchesStatus;
  });

  if (filtered.length === 0) {
    projectEmpty.hidden = false;
    return;
  }

  projectEmpty.hidden = true;

  for (let i = 0; i < filtered.length; i += 1) {
    const p = filtered[i];
    const article = document.createElement("article");
    article.className = "project-card";

    const tagsHtml = p.tags.map((tag) => "<span>" + tag + "</span>").join("");

    article.innerHTML =
      '<div class="project-media">' +
      '<img src="' + p.image + '" alt="' + p.title + '">' +
      "</div>" +
      '<div class="project-body">' +
      '<div class="meta"><span>' + p.category + '</span><span>' + p.budget + "</span></div>" +
      "<h3>" + p.title + "</h3>" +
      '<div class="pill ' + statusClass(p.status) + '">' + p.status + "</div>" +
      "<p>" + p.text + "</p>" +
      '<div class="project-tags">' + tagsHtml + "</div>" +
      "</div>";

    projectGrid.appendChild(article);
  }
}

function startHeroSlider() {
  const slides = document.querySelectorAll(".hero-slide");
  if (slides.length <= 1) {
    return;
  }

  let activeIndex = 0;

  window.setInterval(() => {
    slides[activeIndex].classList.remove("active");
    activeIndex += 1;
    if (activeIndex >= slides.length) {
      activeIndex = 0;
    }
    slides[activeIndex].classList.add("active");
  }, 3600);
}

function animateCounters() {
  const counters = document.querySelectorAll("[data-counter]");
  if (counters.length === 0) {
    return;
  }

  let hasAnimated = false;

  const observer = new IntersectionObserver((entries) => {
    for (let i = 0; i < entries.length; i += 1) {
      const entry = entries[i];
      if (entry.isIntersecting && !hasAnimated) {
        hasAnimated = true;
        for (let j = 0; j < counters.length; j += 1) {
          const el = counters[j];
          const target = Number(el.getAttribute("data-counter"));
          const duration = 1200;
          const startTime = performance.now();

          function updateCounter(now) {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const value = Math.floor(progress * target);
            el.textContent = String(value);
            if (progress < 1) {
              requestAnimationFrame(updateCounter);
            } else {
              el.textContent = String(target);
            }
          }

          requestAnimationFrame(updateCounter);
        }
      }
    }
  }, { threshold: 0.5 });

  counters.forEach((counter) => observer.observe(counter));
}

function setupRevealOnScroll() {
  const revealEls = document.querySelectorAll("[data-reveal]");
  if (revealEls.length === 0) {
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    for (let i = 0; i < entries.length; i += 1) {
      const entry = entries[i];
      if (entry.isIntersecting) {
        entry.target.classList.add("is-visible");
      }
    }
  }, { threshold: 0.18 });

  revealEls.forEach((el) => observer.observe(el));
}

function setupActiveNav() {
  const links = document.querySelectorAll(".main-nav a");
  const sections = document.querySelectorAll("main section[id]");

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) {
        return;
      }

      const id = entry.target.getAttribute("id");
      links.forEach((link) => {
        const href = link.getAttribute("href");
        link.classList.toggle("active", href === "#" + id);
      });
    });
  }, { threshold: 0.5 });

  sections.forEach((section) => observer.observe(section));
}

function openLightbox(src, caption) {
  lightboxImage.src = src;
  lightboxCaption.textContent = caption;
  lightbox.classList.add("open");
  lightbox.setAttribute("aria-hidden", "false");
  document.body.style.overflow = "hidden";
}

function closeLightbox() {
  lightbox.classList.remove("open");
  lightbox.setAttribute("aria-hidden", "true");
  document.body.style.overflow = "";
}

function setupGalleryFilters() {
  if (!galleryFilters) {
    return;
  }

  galleryFilters.addEventListener("click", (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
      return;
    }

    if (!target.classList.contains("chip")) {
      return;
    }

    const filter = target.getAttribute("data-filter") || "all";

    const chips = galleryFilters.querySelectorAll(".chip");
    chips.forEach((chip) => chip.classList.remove("active"));
    target.classList.add("active");

    renderGallery(filter);
  });
}

function setupProjectFilters() {
  searchInput.addEventListener("input", renderProjects);
  categorySelect.addEventListener("change", renderProjects);
  statusSelect.addEventListener("change", renderProjects);
}

function setupLightboxHandlers() {
  lightboxClose.addEventListener("click", closeLightbox);

  lightbox.addEventListener("click", (event) => {
    if (event.target === lightbox) {
      closeLightbox();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeLightbox();
    }
  });

  const openFirstImageBtn = document.getElementById("openFirstImage");
  if (openFirstImageBtn) {
    openFirstImageBtn.addEventListener("click", () => {
      const first = galleryData[0];
      openLightbox(first.image, first.title);
    });
  }
}

function setupQuickButtons() {
  const jumpProjectsBtn = document.getElementById("jumpProjectsBtn");
  if (jumpProjectsBtn) {
    jumpProjectsBtn.addEventListener("click", () => {
      const section = document.getElementById("projects");
      if (section) {
        section.scrollIntoView({ behavior: "smooth" });
      }
    });
  }
}

function setupHeroParallax() {
  const hero = document.getElementById("heroMedia");
  const content = document.getElementById("heroContent");

  if (!hero || !content) {
    return;
  }

  hero.addEventListener("mousemove", (event) => {
    const bounds = hero.getBoundingClientRect();
    const x = (event.clientX - bounds.left) / bounds.width - 0.5;
    const y = (event.clientY - bounds.top) / bounds.height - 0.5;

    content.style.transform = "translate(" + (-x * 10) + "px," + (-y * 8) + "px)";
  });

  hero.addEventListener("mouseleave", () => {
    content.style.transform = "translate(0, 0)";
  });
}

function updateAuthUI() {
  const loginBtn = document.getElementById("loginBtn");
  const logoutBtn = document.getElementById("logoutBtn");
  const phpReturnLink = document.getElementById("phpReturnLink");

  const hasSession = Boolean(
    sessionStorage.getItem("cl_email") ||
    sessionStorage.getItem("cl_nom") ||
    sessionStorage.getItem("cl_role")
  );

  if (loginBtn) {
    loginBtn.classList.toggle("is-hidden", hasSession);
  }

  if (logoutBtn) {
    logoutBtn.classList.toggle("is-hidden", !hasSession);
    logoutBtn.addEventListener("click", () => {
      sessionStorage.removeItem("cl_nom");
      sessionStorage.removeItem("cl_prenom");
      sessionStorage.removeItem("cl_email");
      sessionStorage.removeItem("cl_role");
      window.location.href = "logout.php";
    });
  }

  if (phpReturnLink) {
    phpReturnLink.setAttribute("href", hasSession ? "index.php" : "login.php");
  }
}

renderGallery("all");
renderProjects();
startHeroSlider();
animateCounters();
setupRevealOnScroll();
setupActiveNav();
setupGalleryFilters();
setupProjectFilters();
setupLightboxHandlers();
setupQuickButtons();
setupHeroParallax();
updateAuthUI();
