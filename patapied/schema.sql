-- ============================================================
-- config/schema.sql — Schéma MariaDB — Patapied
-- Exécuter : mysql -u root -p < config/schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS patapied
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE patapied;

-- Création de l'utilisateur applicatif (adapter le mot de passe)
-- CREATE USER IF NOT EXISTS 'patapied_user'@'localhost' IDENTIFIED BY 'patapied_pass';
-- GRANT ALL PRIVILEGES ON patapied.* TO 'patapied_user'@'localhost';
-- FLUSH PRIVILEGES;

-- ------------------------------------------------------------
-- Table : users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(180) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,          -- bcrypt via password_hash()
    first_name  VARCHAR(80)  NOT NULL,
    last_name   VARCHAR(80)  NOT NULL,
    phone       VARCHAR(20)  DEFAULT NULL,
    address     TEXT         DEFAULT NULL,
    city        VARCHAR(100) DEFAULT NULL,
    postal_code VARCHAR(10)  DEFAULT NULL,
    country     VARCHAR(60)  DEFAULT 'France',
    role        ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Compte admin par défaut (mot de passe : Admin1234!)
INSERT IGNORE INTO users (email, password, first_name, last_name, role)
VALUES (
    'admin@patapied.local',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Admin1234!
    'Admin',
    'Patapied',
    'admin'
);

-- ------------------------------------------------------------
-- Table : categories
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name_fr     VARCHAR(100) NOT NULL,
    name_en     VARCHAR(100) NOT NULL,
    slug        VARCHAR(100) NOT NULL UNIQUE,
    sort_order  INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB;

INSERT IGNORE INTO categories (name_fr, name_en, slug, sort_order) VALUES
('Ville', 'Urban', 'ville', 1),
('Sport', 'Sport', 'sport', 2),
('Randonnée', 'Hiking', 'randonnee', 3),
('Enfants', 'Kids', 'enfants', 4);

-- ------------------------------------------------------------
-- Table : products
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id   INT UNSIGNED NOT NULL,
    reference     VARCHAR(40)  NOT NULL UNIQUE,
    name_fr       VARCHAR(160) NOT NULL,
    name_en       VARCHAR(160) NOT NULL,
    description_fr TEXT        DEFAULT NULL,
    description_en TEXT        DEFAULT NULL,
    price         DECIMAL(8,2) NOT NULL,
    stock         INT          NOT NULL DEFAULT 0,
    image_path    VARCHAR(255) DEFAULT NULL,
    is_active     TINYINT(1)  NOT NULL DEFAULT 1,
    created_at    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT IGNORE INTO products (category_id, reference, name_fr, name_en, description_fr, description_en, price, stock) VALUES
(1, 'PAT-URB-001', 'Derby Ergonomique Urban', 'Urban Ergonomic Derby',
 'Semelle orthopédique intégrée, cuir pleine fleur, confort toute la journée.',
 'Built-in orthopedic insole, full-grain leather, all-day comfort.', 129.90, 45),
(1, 'PAT-URB-002', 'Oxford Slim City', 'Slim City Oxford',
 'Silhouette affinée, cuir verni, idéale pour les environnements professionnels.',
 'Slimline silhouette, patent leather, ideal for professional environments.', 149.90, 30),
(2, 'PAT-SPT-001', 'Runner Pro X1', 'Runner Pro X1',
 'Mousse à mémoire de forme, grip renforcé, pour les longues distances.',
 'Memory foam, reinforced grip, built for long distances.', 89.90, 60),
(3, 'PAT-RDO-001', 'Trail Confort 500', 'Comfort Trail 500',
 'Semelle Vibram, waterproof, légèreté optimisée pour les sentiers.',
 'Vibram outsole, waterproof, weight-optimized for trails.', 109.90, 25),
(4, 'PAT-KID-001', 'Mini Ergopied Junior', 'Mini Ergopied Junior',
 'Soutien de voûte plantaire, lacets élastiques, croissance respectée.',
 'Arch support, elastic laces, growth-respectful design.', 59.90, 80);

-- ------------------------------------------------------------
-- Table : orders
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    status          ENUM('pending','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    total_amount    DECIMAL(10,2) NOT NULL,
    shipping_address TEXT         NOT NULL,
    payment_method  VARCHAR(50)   DEFAULT 'card',
    notes           TEXT          DEFAULT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table : order_items
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id    INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    quantity    INT          NOT NULL DEFAULT 1,
    unit_price  DECIMAL(8,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table : cart (panier persistant)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cart (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    quantity    INT          NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_product (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;
