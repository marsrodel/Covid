After magbutang ug database, i run sab ni silag apil:

----
(Handles Add Patient form submission.)

DELIMITER $$

CREATE PROCEDURE sp_add_patient (
   IN p_first_name  VARCHAR(50),
   IN p_last_name   VARCHAR(50),
   IN p_gender      ENUM('Male','Female'),
   IN p_age         INT,
   IN p_location_id INT
 )
 BEGIN
   INSERT INTO patient (first_name, last_name, gender, age, location_id)
   VALUES (p_first_name, p_last_name, p_gender, p_age, p_location_id);
 END$$

 DELIMITER ;
----

----
(Handles Add Case form submission.)

 DELIMITER $$

 CREATE PROCEDURE sp_add_case (
   IN p_test_date  DATE,
   IN p_patient_id INT,
   IN p_result     ENUM('Positive','Negative'),
   IN p_severity   VARCHAR(20),
   IN p_vaccine_id INT,
   IN p_lab_id     INT
 )
 BEGIN
   INSERT INTO covid_cases (test_date, patient_id, result, severity, vaccine_id, lab_id)
   VALUES (p_test_date, p_patient_id, p_result, p_severity, p_vaccine_id, p_lab_id);
 END$$

 DELIMITER ;
----

----
(Handles Delete Case requests.)

 Trigger definition (run once in MySQL, not on every request):

 DELIMITER $$

 CREATE TRIGGER trg_delete_cases_for_patient
   AFTER DELETE ON patient
   FOR EACH ROW
 BEGIN
   DELETE FROM covid_cases WHERE patient_id = OLD.patient_id;
 END$$

 DELIMITER ;
----