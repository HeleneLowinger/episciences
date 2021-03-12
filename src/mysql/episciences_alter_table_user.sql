// see /script/New_fields_update_user.php
ALTER TABLE USER
    ADD PRIMARY KEY (`UID`),
    ADD UNIQUE KEY `U_USERNAME` (`USERNAME`),
    ADD KEY `API_PASSWORD` (`API_PASSWORD`),
    ADD KEY `EMAIL` (`EMAIL`),
    ADD KEY `IS_VALID` (`IS_VALID`),
    ADD KEY `FIRSTNAME` (`FIRSTNAME`),
    ADD KEY `LASTNAME` (`LASTNAME`);