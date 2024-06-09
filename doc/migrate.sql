create database youtool;
GRANT ALL PRIVILEGES ON youtool.* TO 'youtool'@'%' IDENTIFIED BY '';

use youtool;

CREATE TABLE video (
    id int NOT NULL AUTO_INCREMENT,
    youtubeId varchar(60) NOT NULL,
    description text,
    generated boolean,
    submitted boolean,
    active boolean,
    PRIMARY KEY (id),
);

CREATE TABLE category_to_video (
    categoryId int NOT NULL,
    videoId int NOT NULL,
    PRIMARY KEY (categoryId, videoId)
);

CREATE TABLE block (
    id int NOT NULL AUTO_INCREMENT,
    type varchar(60) NOT NULL,
    startTime TIMESTAMP,
    endTime TIMESTAMP,
    override_categories boolean,
    snippet text,
    active boolean,
    changed boolean,
    PRIMARY KEY (id)
);

CREATE TABLE category_to_block (
    categoryId int NOT NULL,
    blockId int NOT NULL,
    PRIMARY KEY (categoryId, blockId)
);

CREATE TABLE video_to_block (
    videoId int NOT NULL,
    blockId int NOT NULL,
    PRIMARY KEY (videoId, blockId)
);


CREATE TABLE category (
    id int NOT NULL AUTO_INCREMENT,
    name varchar(60) NOT NULL,
    PRIMARY KEY (id)
);


CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    channel_id VARCHAR(60) NOT NULL,
    access_token VARCHAR(255) NOT NULL,
    refresh_token VARCHAR(255) NOT NULL,
    expire_time TIMESTAMP,
    write_access BOOLEAN,
    payed_until TIMESTAMP,
    auth_key VARCHAR(40),
    PRIMARY KEY (id)
);
