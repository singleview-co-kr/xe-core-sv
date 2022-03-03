DELETE
FROM `svestudio_gross_compiled_daily_log`
WHERE `logdate` LIKE CONCAT(?, '%')