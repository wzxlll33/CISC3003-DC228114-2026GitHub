(() => {
    class ReviewManager {
        constructor(options = {}) {
            this.root = options.root;
            this.restaurantId = Number(options.restaurantId || this.root?.dataset.restaurantId || 0);
            this.currentUserId = Number(options.currentUserId || 0);
            this.form = this.root?.querySelector('[data-review-form]');
            this.toggleButton = this.root?.querySelector('[data-review-toggle]');
            this.ratingInput = this.root?.querySelector('[data-review-rating]');
            this.commentInput = this.root?.querySelector('[data-review-comment]');
            this.foodSelect = this.root?.querySelector('[data-review-food]');
            this.reviewList = this.root?.querySelector('[data-review-list]');
            this.modalReviewList = document.querySelector('[data-review-modal-list]');
            this.ratingBars = this.root?.querySelector('[data-rating-bars]');
            this.successElement = this.root?.querySelector('[data-review-success]');
            this.errorElement = this.root?.querySelector('[data-review-error]');
            this.submittedState = this.root?.querySelector('[data-review-submitted-state]');
            this.labels = {
                guest: 'Guest',
                delete: 'Delete',
                reviewsCount: ':count reviews',
                validationError: 'Please choose a rating and write at least 10 characters.',
                submitSuccess: 'Review submitted successfully.',
                submitError: 'Unable to submit review.',
                deleteSuccess: 'Review deleted.',
                deleteError: 'Unable to delete review.',
                reviewAlreadySubmitted: 'You have reviewed this restaurant',
                ...(options.labels || {}),
            };
            this.state = {
                rating: 0,
            };

            if (!this.root || !this.restaurantId || !window.api) {
                return;
            }

            this.bindToggle();
            this.bindStars();
            this.bindForm();
            this.bindDelete();
        }

        static init(options = {}) {
            return new ReviewManager(options);
        }

        bindToggle() {
            this.toggleButton?.addEventListener('click', () => {
                if (!this.form) {
                    return;
                }

                this.form.hidden = false;
                this.form.style.display = 'block';
                this.commentInput?.focus();
            });
        }

        bindStars() {
            this.root.querySelectorAll('[data-star-value]').forEach((button) => {
                button.addEventListener('click', () => {
                    this.setRating(Number(button.dataset.starValue || 0));
                });
            });
        }

        bindForm() {
            this.form?.addEventListener('submit', async (event) => {
                event.preventDefault();
                this.hideMessages();

                const rating = Number(this.ratingInput?.value || 0);
                const comment = String(this.commentInput?.value || '').trim();

                if (rating < 1 || comment.length < 10) {
                    this.showError(this.labels.validationError);
                    return;
                }

                const submitButton = this.form.querySelector('[type="submit"]');

                if (submitButton instanceof HTMLButtonElement) {
                    submitButton.disabled = true;
                }

                try {
                    await window.api.post(`/api/restaurants/${encodeURIComponent(String(this.restaurantId))}/reviews`, {
                        rating,
                        comment,
                        food_id: this.foodSelect?.value ? Number(this.foodSelect.value) : null,
                    });

                    this.showSuccess(this.labels.submitSuccess);
                    this.resetForm();
                    await this.refreshReviews();
                } catch (error) {
                    const validationMessage = Array.isArray(error?.payload?.errors)
                        ? error.payload.errors.join(' ')
                        : error?.message;
                    this.showError(validationMessage || this.labels.submitError);
                } finally {
                    if (submitButton instanceof HTMLButtonElement) {
                        submitButton.disabled = false;
                    }
                }
            });
        }

        bindDelete() {
            const handleDelete = async (event) => {
                const button = event.target.closest('[data-review-delete]');

                if (!button) {
                    return;
                }

                event.preventDefault();
                this.hideMessages();

                try {
                    await window.api.delete(`/api/reviews/${encodeURIComponent(String(button.dataset.reviewId || ''))}`);
                    this.showSuccess(this.labels.deleteSuccess);
                    await this.refreshReviews();

                    if (this.toggleButton) {
                        this.toggleButton.hidden = false;
                    }
                } catch (error) {
                    this.showError(error?.message || this.labels.deleteError);
                }
            };

            [this.reviewList, this.modalReviewList].filter(Boolean).forEach((list) => {
                list.addEventListener('click', handleDelete);
            });
        }

        setRating(value) {
            this.state.rating = Number(value);

            if (this.ratingInput) {
                this.ratingInput.value = String(this.state.rating);
            }

            this.root.querySelectorAll('[data-star-value]').forEach((button) => {
                const starValue = Number(button.dataset.starValue || 0);
                button.classList.toggle('is-active', starValue <= this.state.rating);
            });
        }

        async refreshReviews() {
            const payload = await window.api.get(`/api/restaurants/${encodeURIComponent(String(this.restaurantId))}/reviews`);
            const reviews = Array.isArray(payload?.reviews) ? payload.reviews : [];
            const stats = payload?.stats && typeof payload.stats === 'object' ? payload.stats : {};
            this.renderReviews(reviews);
            this.renderStats(reviews, stats);
        }

        renderReviews(reviews) {
            const reviewLists = [this.reviewList, this.modalReviewList].filter(Boolean);

            if (!reviewLists.length) {
                return;
            }

            const markup = reviews.map((review) => {
                const isOwnReview = Number(review.user_id || 0) === this.currentUserId;
                return `
                    <article class="review-card" data-review-id="${this.escapeAttribute(review.id)}">
                        <div class="review-card__header">
                            <div class="review-card__identity">
                                <div class="review-card__user"><strong>${this.escapeHtml(review.username || this.labels.guest)}</strong></div>
                                <div class="review-card__rating">${this.renderStars(review.rating)} · ${this.escapeHtml(String(review.rating || 0))}/5</div>
                            </div>
                            <div class="review-card__context">
                                <div class="review-card__date">${this.escapeHtml(String(review.created_at || '').slice(0, 10))}</div>
                                ${review.food_name ? `<div class="review-card__food">${this.escapeHtml(review.food_name)}</div>` : ''}
                            </div>
                        </div>
                        <p class="review-card__comment">${this.escapeHtml(review.comment || '')}</p>
                        ${isOwnReview ? `<div><button type="button" class="btn btn--outline btn--sm" data-review-delete data-review-id="${this.escapeAttribute(review.id)}">${this.escapeHtml(this.labels.delete)}</button></div>` : ''}
                    </article>
                `;
            }).join('');
            const hasOwnReview = reviews.some((review) => Number(review.user_id || 0) === this.currentUserId);

            reviewLists.forEach((list) => {
                list.innerHTML = markup;
            });

            if (this.toggleButton) {
                this.toggleButton.hidden = hasOwnReview;
            }

            if (this.submittedState) {
                this.submittedState.hidden = !hasOwnReview;
                this.submittedState.textContent = hasOwnReview ? this.labels.reviewAlreadySubmitted : '';
            }

            document.querySelectorAll('[data-detail-more-button="reviews"]').forEach((button) => {
                button.setAttribute('data-detail-more-count', String(reviews.length));
            });

            window.dispatchEvent(new CustomEvent('restaurant-reviews-updated', {
                detail: { count: reviews.length },
            }));
        }

        renderStats(reviews, stats) {
            const reviewCount = Array.isArray(reviews) ? reviews.length : 0;
            const total = reviews.reduce((sum, review) => sum + Number(review.rating || 0), 0);
            const average = reviewCount > 0 ? total / reviewCount : 0;

            document.querySelectorAll('[data-review-count]').forEach((element) => {
                element.textContent = element.closest('.stat-card')
                    ? String(reviewCount)
                    : this.labels.reviewsCount.replace(':count', String(reviewCount));
            });

            document.querySelectorAll('[data-average-rating]').forEach((element) => {
                element.textContent = average.toFixed(1);
            });

            document.querySelectorAll('[data-average-stars]').forEach((element) => {
                element.textContent = this.renderStars(average);
            });

            if (!this.ratingBars) {
                return;
            }

            this.ratingBars.querySelectorAll('[data-rating-row]').forEach((row) => {
                const score = Number(row.dataset.score || 0);
                const count = Number(stats[String(score)] ?? stats[score] ?? 0);
                const fill = row.querySelector('.rating-summary__fill');
                const countElement = row.querySelector('[data-rating-count]');
                const width = reviewCount > 0 ? (count / reviewCount) * 100 : 0;

                if (fill) {
                    fill.style.width = `${width}%`;
                }

                if (countElement) {
                    countElement.textContent = String(count);
                }
            });
        }

        resetForm() {
            if (!this.form) {
                return;
            }

            this.form.reset();
            this.setRating(0);
            this.form.hidden = true;
            this.form.style.display = 'none';
        }

        showSuccess(message) {
            if (!this.successElement) {
                return;
            }

            this.successElement.hidden = false;
            this.successElement.textContent = message;
        }

        showError(message) {
            if (!this.errorElement) {
                return;
            }

            this.errorElement.hidden = false;
            this.errorElement.textContent = message;
        }

        hideMessages() {
            if (this.successElement) {
                this.successElement.hidden = true;
                this.successElement.textContent = '';
            }

            if (this.errorElement) {
                this.errorElement.hidden = true;
                this.errorElement.textContent = '';
            }
        }

        renderStars(rating) {
            const value = Number(rating || 0);
            const filled = Math.max(0, Math.min(5, Math.round(value)));
            return this.escapeHtml(`${'★'.repeat(filled)}${'☆'.repeat(5 - filled)}`);
        }

        escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        escapeAttribute(value) {
            return this.escapeHtml(value);
        }
    }

    window.ReviewManager = {
        init(options) {
            return ReviewManager.init(options);
        },
    };
})();
