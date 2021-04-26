<?
//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        1 February 2012
//Copyright: (C) 2012, Sergey Stoyan
//********************************************************************************************

$ABSPATH = dirname(__FILE__)."/..";
include_once("$ABSPATH/common/logger.php");

SELECT county, property_status, COUNT(cid) FROM kh_deals WHERE property_status<>'' GROUP BY county, property_status ORDER BY county, property_status

SELECT county, property_type, COUNT(cid) FROM kh_deals WHERE property_type<>'' GROUP BY county, property_type ORDER BY county, property_type

SELECT county, bedrooms, COUNT(cid) FROM kh_deals WHERE bedrooms<>'' GROUP BY county, bedrooms ORDER BY county, bedrooms

SELECT county, is_sold, COUNT(cid) FROM kh_deals WHERE is_sold<>'' GROUP BY county, is_sold ORDER BY county, is_sold

SELECT county, tenure, COUNT(cid) FROM kh_deals WHERE tenure<>'' GROUP BY county, tenure ORDER BY county, tenure

SELECT county, features, COUNT(cid) FROM kh_deals WHERE features<>'' GROUP BY county, features ORDER BY county, features

CREATE VIEW view_errors AS
SELECT 
FROM_UNIXTIME(error_timestamp) AS error_time, error_code, error_description, cid, record_status, update_timestamp, image_url, thumbnail_url, source_url, price, headline, street, county,	town,	postal_code,	postal_code_short,	property_type,	bedrooms,	synchronized_time,	is_sold,	tenure,	deal_source,	features,	property_status,	description,	listing_timestamp,	latitude,	longitude
 FROM kh_deals_errors INNER JOIN kh_deals_invalid ON kh_deals_errors.did=kh_deals_invalid.did
 ORDER BY error_time DESC

SELECT (SELECT COUNT(did) FROM (SELECT did FROM kh_deals_errors WHERE error_timestamp >= (SELECT UNIX_TIMESTAMP(MIN(synchronized_time)) FROM kh_deals) GROUP BY did) a)/(SELECT COUNT(did) FROM kh_deals)*100

SELECT (SELECT COUNT(did) FROM kh_deals_invalid WHERE synchronized_time >= (SELECT MIN(synchronized_time) FROM kh_deals))/(SELECT COUNT(did) FROM kh_deals)*100

SELECT property_status, COUNT(cid)/a.c*100 FROM kh_deals, (SELECT COUNT(cid) AS c FROM kh_deals) a WHERE property_status<>'' GROUP BY property_status ORDER BY COUNT(cid) DESC

?>