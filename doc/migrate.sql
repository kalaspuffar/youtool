create database youtool;
GRANT ALL PRIVILEGES ON youtool.* TO 'youtool'@'%' IDENTIFIED BY '';

use youtool;

CREATE TABLE video (
    id INT NOT NULL AUTO_INCREMENT,
    userId INT NOT NULL,
    youtubeId VARCHAR(60) NOT NULL,
    youtubeCategoryId VARCHAR(20) NOT NULL,
    title VARCHAR(120),
    description TEXT,
    generated BOOLEAN NOT NULL DEFAULT 0,
    published BOOLEAN NOT NULL DEFAULT 0,
    generatedAt TIMESTAMP NOT NULL,
    publishedAt TIMESTAMP NOT NULL,
    active BOOLEAN NOT NULL DEFAULT 0,
    internal BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
);

CREATE TABLE category_to_video (
    categoryId INT NOT NULL,
    videoId INT NOT NULL,
    PRIMARY KEY (categoryId, videoId)
);

CREATE TABLE block (
    id INT NOT NULL AUTO_INCREMENT,
    userId INT NOT NULL,
    type VARCHAR(60) NOT NULL,
    startTime TIMESTAMP,
    endTime TIMESTAMP,
    override_categories BOOLEAN NOT NULL DEFAULT 0,
    snippet TEXT,
    active BOOLEAN NOT NULL DEFAULT 0,
    changed BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
);

CREATE TABLE category_to_block (
    categoryId INT NOT NULL,
    blockId INT NOT NULL,
    PRIMARY KEY (categoryId, blockId)
);

CREATE TABLE video_to_block (
    videoId INT NOT NULL,
    blockId INT NOT NULL,
    PRIMARY KEY (videoId, blockId)
);


CREATE TABLE category (
    id INT NOT NULL AUTO_INCREMENT,
    userId INT NOT NULL,
    name VARCHAR(60) NOT NULL,
    PRIMARY KEY (id)
);


CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    channel_id VARCHAR(60) NOT NULL,
    access_token VARCHAR(255) NOT NULL,
    refresh_token VARCHAR(255) NOT NULL,
    expire_time TIMESTAMP,
    write_access BOOLEAN NOT NULL DEFAULT 0,
    payed_until TIMESTAMP,
    auth_key VARCHAR(40),
    PRIMARY KEY (id)
);

CREATE TABLE comment (
    id INT NOT NULL AUTO_INCREMENT,
    parentId INT,
    userId INT NOT NULL,
    videoId INT NOT NULL,
    commentId VARCHAR(60) NOT NULL,
    authorDisplayName VARCHAR(255),
    authorProfileImageUrl VARCHAR(255),
    publishedAt TIMESTAMP NOT NULL,
    textDisplay TEXT,
    likeCount INT NOT NULL DEFAULT 0,
    visible BOOLEAN NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
);


CREATE TABLE quota (
    quota_day DATE,
    count INT,
    PRIMARY KEY (quota_day)
);

CREATE TABLE payment (
    id VARCHAR(255) NOT NULL,
    userId INT NOT NULL,
    quantity FLOAT NOT NULL,
    price FLOAT NOT NULL,
    payed FLOAT,
    status VARCHAR(255),
    paymentDate TIMESTAMP,
    email VARCHAR(255),
    response TEXT,
    PRIMARY KEY (id)
);

CREATE TABLE titles (
    id INT NOT NULL AUTO_INCREMENT,
    userId INT NOT NULL,
    categoryId INT NOT NULL,
    title VARCHAR(255),
    PRIMARY KEY (id)
);