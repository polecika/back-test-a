CREATE TABLE 'product_passport' (
    'id' INT AUTO_INCREMENT NOT NULL,
    'name' VARCHAR(100) NOT NULL,
    'US_TIER_LIST' VARCHAR(25) DEFAULT NULL,
    'SHORT_NAME' VARCHAR(25) NOT NULL,
    PRIMARY KEY('id'));
CREATE TABLE 'asin' (
      'id' INT AUTO_INCREMENT NOT NULL,
      'product_password_id' INT NOT NULL,
      'type' VARCHAR(25) NOT NULL,
      'code' VARCHAR(25) DEFAULT '-' ,
      PRIMARY KEY('id'),
      UNIQUE KEY 'UNIQ_CODE' ('code'),
      CONSTRAINT 'FK_PRODUCT_PASSWORD' FOREIGN KEY ('product_password_id') REFERENCES 'product_password' ('id') ON DELETE CASCADE);
ALTER TABLE 'asin' ADD CONSTRAINT 'FK_PRODUCT_PASSWORD' FOREIGN KEY ('user_id') REFERENCES 'users' ('id')

