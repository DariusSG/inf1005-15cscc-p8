export function modsArray(DB) {
  return Object.values(DB.modules);
}

export function avgRating(module) {
  if (!module.reviews.length) return 0;
  return (
    module.reviews.reduce((sum, review) => sum + review.rating, 0) /
    module.reviews.length
  ).toFixed(1);
}

export function averageMetric(module, key) {
  if (!module.reviews.length) return "—";
  return (
    module.reviews.reduce((sum, review) => sum + Number(review[key] || 0), 0) /
    module.reviews.length
  ).toFixed(1);
}

export function initials(name) {
  return String(name || "")
    .split(" ")
    .filter(Boolean)
    .map((part) => part[0])
    .join("");
}

export function formatStars(value) {
  return "★".repeat(Number(value || 0));
}

/**
 * Returns true only for @sit.singaporetech.edu.sg addresses.
 * Mirrors the backend VerificationService::SIT_DOMAIN check.
 */
export function isSitEmail(email) {
  return /@sit\.singaporetech\.edu\.sg$/i.test(String(email || "").trim());
}

export function findReviewById(DB, moduleCode, reviewId) {
  const module = DB.modules[moduleCode];
  if (!module) return null;
  return module.reviews.find((review) => review.id === reviewId) || null;
}

export function getExistingUserReview(DB, moduleCode, userEmail) {
  const module = DB.modules[moduleCode];
  if (!module) return null;
  return (
    module.reviews.find(
      (review) =>
        review.email.toLowerCase() === String(userEmail || "").toLowerCase()
    ) || null
  );
}

export function canUserWriteReview(DB, moduleCode) {
  if (!DB.user) return false;
  if (DB.user.role === "admin") return false;
  return true;
}

export function canUserVoteOrReport(DB) {
  return Boolean(DB.user && DB.user.role !== "admin");
}

export function matchesSearch(value, query) {
  return String(value || "")
    .toLowerCase()
    .includes(String(query || "").toLowerCase());
}

export function filterModulesList(DB, query) {
  const q = String(query || "").toLowerCase();
  return modsArray(DB).filter((module) => {
    const facultyMatch = DB.faculty === "All" || module.faculty === DB.faculty;
    const searchMatch =
      !q ||
      module.code.toLowerCase().includes(q) ||
      module.name.toLowerCase().includes(q) ||
      module.desc.toLowerCase().includes(q);
    return facultyMatch && searchMatch;
  });
}

export function filterTutorsList(DB, query) {
  const q = String(query || "").toLowerCase();
  return DB.tutors.filter(
    (tutor) =>
      !q ||
      tutor.name.toLowerCase().includes(q) ||
      tutor.modules.join(" ").toLowerCase().includes(q) ||
      tutor.bio.toLowerCase().includes(q)
  );
}

export function filterStudyGroupsList(DB, query) {
  const q = String(query || "").toLowerCase();
  return DB.studyGroups.filter(
    (group) =>
      !q ||
      group.title.toLowerCase().includes(q) ||
      group.module.toLowerCase().includes(q) ||
      group.desc.toLowerCase().includes(q) ||
      group.location.toLowerCase().includes(q)
  );
}

export function filterHelpList(DB, query) {
  const q = String(query || "").toLowerCase();
  return DB.helpReqs.filter(
    (item) =>
      !q ||
      item.title.toLowerCase().includes(q) ||
      item.module.toLowerCase().includes(q) ||
      item.desc.toLowerCase().includes(q) ||
      item.author.toLowerCase().includes(q)
  );
}