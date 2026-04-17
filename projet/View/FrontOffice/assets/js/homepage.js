const galleryData = [
  {
    title: "Tissage de Nabeul",
    category: "textile",
    size: "wide",
    image: "https://images.unsplash.com/photo-1616628182509-6f64f18f561c?auto=format&fit=crop&w=1200&q=80"
  },
  {
    title: "Email ceramique",
    category: "ceramique",
    size: "square",
    image: "https://images.unsplash.com/photo-1618220179428-22790b461013?auto=format&fit=crop&w=900&q=80"
  },
  {
    title: "Sculpture bois",
    category: "bois",
    size: "tall",
    image: "https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=900&q=80"
  },
  {
    title: "Collection bijoux",
    category: "bijoux",
    size: "square",
    image: "https://images.unsplash.com/photo-1599643477877-530eb83abc8e?auto=format&fit=crop&w=900&q=80"
  },
  {
    title: "Finitions broderie",
    category: "textile",
    size: "square",
    image: "https://images.unsplash.com/photo-1517048676732-d65bc937f952?auto=format&fit=crop&w=900&q=80"
  },
  {
    title: "Argile locale",
    category: "ceramique",
    size: "wide",
    image: "https://images.unsplash.com/photo-1491557345352-5929e343eb89?auto=format&fit=crop&w=1200&q=80"
  },
  {
    title: "MosaIque artisanale",
    category: "ceramique",
    size: "square",
    image: "https://images.unsplash.com/photo-1523413651479-597eb2da0ad6?auto=format&fit=crop&w=900&q=80"
  },
  {
    title: "Bois grave",
    category: "bois",
    size: "square",
    image: "https://images.unsplash.com/photo-1505692952047-1a78307da8f2?auto=format&fit=crop&w=900&q=80"
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
    image: "https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=1100&q=80"
  },
  {
    title: "Atelier ceramique utilitaire",
    category: "Ceramique",
    status: "En cours",
    budget: "2300 DT",
    text: "Collection vaisselle locale avec email alimentaire et packaging ecoresponsable.",
    tags: ["argile", "email", "branding"],
    image: "https://images.unsplash.com/photo-1517685352821-92cf88aee5a5?auto=format&fit=crop&w=1100&q=80"
  },
  {
    title: "Mobilier bois olive",
    category: "Bois",
    status: "Ouvert",
    budget: "3200 DT",
    text: "Pieces haut de gamme inspirees des formes tunisiennes traditionnelles.",
    tags: ["ebenisterie", "finitions", "livraison"],
    image: "https://images.unsplash.com/photo-1461418559055-6f020c5a91e7?auto=format&fit=crop&w=1100&q=80"
  },
  {
    title: "Bijoux cuivre grave",
    category: "Bijouterie",
    status: "Ferme",
    budget: "950 DT",
    text: "Mini collection avec gravure fine et storytelling digital pour reseaux sociaux.",
    tags: ["gravure", "shooting", "vente web"],
    image: "https://images.unsplash.com/photo-1573408301185-9146fe634ad0?auto=format&fit=crop&w=1100&q=80"
  },
  {
    title: "Textile maison naturel",
    category: "Textile",
    status: "En cours",
    budget: "1400 DT",
    text: "Ligne de coussins et nappes avec colorants naturels et finitions manuelles.",
    tags: ["teinture", "tissage", "distribution"],
    image: "https://images.unsplash.com/photo-1598300056393-4aac492f4344?auto=format&fit=crop&w=1100&q=80"
  },
  {
    title: "Serie pots design",
    category: "Ceramique",
    status: "Ouvert",
    budget: "1700 DT",
    text: "Edition decorative pour concept stores, avec identite visuelle epuree.",
    tags: ["moulage", "email", "catalogue"],
    image: "https://images.unsplash.com/photo-1565193566173-7a0ee3dbe261?auto=format&fit=crop&w=1100&q=80"
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
