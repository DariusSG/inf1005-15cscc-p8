import { DB } from "../data/mockDB.js";

const wait = (ms = 0) => new Promise((resolve) => setTimeout(resolve, ms));

const clone = (value) => JSON.parse(JSON.stringify(value));

/*
  🔌 BACKEND_CALL placeholders preserved as a PHP-ready integration layer.

  Later PHP mapping examples:
  - GET  /api/auth/session.php
  - POST /api/auth/login.php
  - POST /api/auth/register.php
  - POST /api/auth/verify-otp.php
  - POST /api/auth/send-otp.php
  - POST /api/auth/logout.php
  - GET  /api/modules.php
  - GET  /api/module.php?code=ICT2003
  - POST /api/reviews.php
  - PUT  /api/reviews.php?id=123
  - POST /api/review-vote.php
  - POST /api/review-report.php
  - POST /api/review-comment.php
  - GET  /api/tutors.php
  - POST /api/tutors.php
  - GET  /api/study-groups.php
  - POST /api/study-groups.php
  - GET  /api/help-requests.php
  - POST /api/help-requests.php
  - GET  /api/admin/reported-reviews.php
*/

export const api = {
  async getSession() {
    // 🔌 BACKEND_CALL: GET /api/auth/session — check if user is logged in
    await wait();
    return clone(DB.user);
  },

  async login({ email, password }) {
    // 🔌 BACKEND_CALL: POST /api/auth/login {email, password}
    await wait();

    const normalizedEmail = String(email || "").trim().toLowerCase();
    const user = DB.users[normalizedEmail];

    if (!user || user.password !== password) {
      return {
        success: false,
        message: "Invalid email or password.",
      };
    }

    DB.user = user;

    return {
      success: true,
      user: clone(DB.user),
    };
  },

  async registerPending({ name, email, password }) {
    // 🔌 BACKEND_CALL: POST /api/auth/register {name, email, password}
    await wait();

    DB._pending = {
      name,
      email,
      password,
    };

    return {
      success: true,
      message: `OTP sent to ${email} (demo: use 123456)`,
    };
  },

  async verifyOtp({ otp }) {
    // 🔌 BACKEND_CALL: POST /api/auth/verify-otp {email, otp}
    await wait();

    const digits = String(otp || "");

    if ((digits === "123456" || digits.length === 6) && DB._pending) {
      const pending = DB._pending;

      DB.users[pending.email] = {
        email: pending.email,
        name: pending.name,
        password: pending.password,
        role: "student",
        verified: true,
      };

      DB.user = DB.users[pending.email];
      delete DB._pending;

      return {
        success: true,
        user: clone(DB.user),
      };
    }

    return {
      success: false,
      message: "Invalid OTP. Try 123456.",
    };
  },

  async resendOtp() {
    // 🔌 BACKEND_CALL: POST /api/auth/send-otp {email} — resend
    await wait();
    return {
      success: true,
      message: "OTP resent! (demo: 123456)",
    };
  },

  async logout() {
    // 🔌 BACKEND_CALL: POST /api/auth/logout — destroy session
    await wait();
    DB.user = null;
    return {
      success: true,
    };
  },

  async getModules() {
    // 🔌 BACKEND_CALL: GET /api/modules
    await wait();
    return clone(Object.values(DB.modules));
  },

  async getModuleByCode(code) {
    // 🔌 BACKEND_CALL: GET /api/modules/{code}
    // 🔌 BACKEND_CALL: GET /api/modules/{code}/reviews
    await wait();
    return clone(DB.modules[code] || null);
  },

  async submitReview(payload) {
    // 🔌 BACKEND_CALL: POST /api/reviews {moduleCode, rating, title, content, workload, difficulty, usefulness}
    // 🔌 BACKEND_CALL: PUT /api/reviews/{id} — for editing existing review
    await wait();

    const module = DB.modules[payload.moduleCode];
    if (!module) {
      return {
        success: false,
        message: "Module not found.",
      };
    }

    if (!DB.user) {
      return {
        success: false,
        message: "Please sign in first.",
      };
    }

    if (DB.user.role === "admin") {
      return {
        success: false,
        message: "Admins cannot write reviews.",
      };
    }

    if (payload.editingId) {
      const review = module.reviews.find((item) => item.id === payload.editingId);

      if (!review) {
        return {
          success: false,
          message: "Review not found.",
        };
      }

      review.rating = payload.rating;
      review.title = payload.title;
      review.content = payload.content;
      review.workload = payload.workload;
      review.difficulty = payload.difficulty;
      review.usefulness = payload.usefulness;

      return {
        success: true,
        review: clone(review),
        mode: "edit",
      };
    }

    const review = {
      id: DB.nextId++,
      author: DB.user.name,
      email: DB.user.email,
      rating: payload.rating,
      title: payload.title,
      content: payload.content,
      workload: payload.workload,
      difficulty: payload.difficulty,
      usefulness: payload.usefulness,
      upvotes: 0,
      downvotes: 0,
      date: new Date().toISOString().slice(0, 10),
      comments: [],
      userVotes: {},
      reportedBy: [],
    };

    module.reviews.unshift(review);

    return {
      success: true,
      review: clone(review),
      mode: "create",
    };
  },

  async toggleVote({ moduleCode, reviewId, type }) {
    // 🔌 BACKEND_CALL: POST /api/review-vote.php
    await wait();

    const module = DB.modules[moduleCode];
    const review = module?.reviews.find((item) => item.id === reviewId);

    if (!review) {
      return {
        success: false,
        message: "Review not found.",
      };
    }

    if (!DB.user) {
      return {
        success: false,
        message: "Please sign in to vote.",
      };
    }

    if (DB.user.role === "admin") {
      return {
        success: false,
        message: "Admins cannot vote on reviews.",
      };
    }

    const userEmail = DB.user.email;
    const previous = review.userVotes[userEmail] || null;

    if (previous === type) {
      if (type === "up") review.upvotes -= 1;
      if (type === "down") review.downvotes -= 1;
      delete review.userVotes[userEmail];
    } else {
      if (previous === "up") review.upvotes -= 1;
      if (previous === "down") review.downvotes -= 1;

      review.userVotes[userEmail] = type;

      if (type === "up") review.upvotes += 1;
      if (type === "down") review.downvotes += 1;
    }

    return {
      success: true,
      review: clone(review),
    };
  },

  async toggleReport({ moduleCode, reviewId }) {
    // 🔌 BACKEND_CALL: POST /api/review-report.php
    await wait();

    const module = DB.modules[moduleCode];
    const review = module?.reviews.find((item) => item.id === reviewId);

    if (!review) {
      return {
        success: false,
        message: "Review not found.",
      };
    }

    if (!DB.user) {
      return {
        success: false,
        message: "Please sign in to report.",
      };
    }

    if (DB.user.role === "admin") {
      return {
        success: false,
        message: "Admins cannot report reviews.",
      };
    }

    const userEmail = DB.user.email;
    const existingIndex = review.reportedBy.indexOf(userEmail);

    if (existingIndex >= 0) {
      review.reportedBy.splice(existingIndex, 1);
    } else {
      review.reportedBy.push(userEmail);
    }

    const existingAdminReportIndex = DB.reported.findIndex(
      (item) => item.reviewId === review.id && item.mc === moduleCode
    );

    if (review.reportedBy.length > 0) {
      if (existingAdminReportIndex >= 0) {
        DB.reported[existingAdminReportIndex].count = review.reportedBy.length;
      } else {
        DB.reported.push({
          reviewId: review.id,
          mc: moduleCode,
          title: review.title,
          count: review.reportedBy.length,
          reason: "User reported review",
        });
      }
    } else if (existingAdminReportIndex >= 0) {
      DB.reported.splice(existingAdminReportIndex, 1);
    }

    return {
      success: true,
      review: clone(review),
      reported: clone(DB.reported),
    };
  },

  async addComment({ moduleCode, reviewId, text }) {
    // 🔌 BACKEND_CALL: POST /api/review-comment.php
    await wait();

    const module = DB.modules[moduleCode];
    const review = module?.reviews.find((item) => item.id === reviewId);

    if (!review) {
      return {
        success: false,
        message: "Review not found.",
      };
    }

    if (!DB.user) {
      return {
        success: false,
        message: "Please sign in to comment.",
      };
    }

    review.comments.push({
      a: DB.user.name,
      t: text,
      time: "Just now",
    });

    return {
      success: true,
      review: clone(review),
    };
  },

  async getTutors() {
    // 🔌 BACKEND_CALL: GET /api/tutors?search={query}
    await wait();
    return clone(DB.tutors);
  },

  async createTutorListing() {
    // 🔌 BACKEND_CALL: POST /api/tutors — create tutor listing
    await wait();
    return {
      success: true,
      message: "Tutor listing flow placeholder for PHP backend.",
    };
  },

  async getStudyGroups() {
    // 🔌 BACKEND_CALL: GET /api/study-groups?search={query}
    await wait();
    return clone(DB.studyGroups);
  },

  async createStudyGroup() {
    // 🔌 BACKEND_CALL: POST /api/study-groups — create new group
    await wait();
    return {
      success: true,
      message: "Create group flow placeholder for PHP backend.",
    };
  },

  async getHelpRequests() {
    // 🔌 BACKEND_CALL: GET /api/help-requests?search={query}
    await wait();
    return clone(DB.helpReqs);
  },

  async createHelpRequest() {
    // 🔌 BACKEND_CALL: POST /api/help-requests — create help request
    await wait();
    return {
      success: true,
      message: "Help request flow placeholder for PHP backend.",
    };
  },

  async getReportedReviews() {
    // 🔌 BACKEND_CALL: GET /api/admin/reported-reviews — reported_reviews table
    await wait();
    return clone(DB.reported);
  },
};