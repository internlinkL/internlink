CREATE TABLE IF NOT EXISTS saved_offers (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id INT UNSIGNED NOT NULL,
    offer_id   INT UNSIGNED NOT NULL,
    saved_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_saved (student_id, offer_id),
    CONSTRAINT fk_saved_student FOREIGN KEY (student_id) REFERENCES users (id)                ON DELETE CASCADE,
    CONSTRAINT fk_saved_offer   FOREIGN KEY (offer_id)   REFERENCES internship_offers (id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
