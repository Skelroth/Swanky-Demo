CREATE TABLE IF NOT EXISTS ajxp_simple_store (
   object_id VARCHAR(255) NOT NULL,
   store_id VARCHAR(50) NOT NULL,
   serialized_data LONGTEXT NULL,
   binary_data LONGBLOB NULL,
   related_object_id VARCHAR(255) NULL,
   PRIMARY KEY(object_id, store_id)
);