CREATE DATABASE CasinoDB;
USE CasinoDB;

-- Users table
CREATE TABLE tblUser (
    idUser INT AUTO_INCREMENT PRIMARY KEY,
    dtUsername VARCHAR(50) UNIQUE NOT NULL,
    dtPasswordHash VARCHAR(255) NOT NULL,
    dtEmail VARCHAR(100) UNIQUE NOT NULL,
    dtBalance DECIMAL(10,2) DEFAULT 0.00,
    dtCreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dtLastLogin TIMESTAMP NULL
);

-- Games table
CREATE TABLE tblGame (
    idGame INT AUTO_INCREMENT PRIMARY KEY,
    dtName VARCHAR(100) NOT NULL,
    dtType ENUM('slot', 'poker', 'blackjack', 'roulette', 'baccarat', 'sportsbet') NOT NULL,
    dtMinBet DECIMAL(10,2) DEFAULT 0.00,
    dtMaxBet DECIMAL(10,2) DEFAULT 10000.00
);

-- Transactions Table
CREATE TABLE tblTransaction (
    idTransaction INT AUTO_INCREMENT PRIMARY KEY,
    fiUser INT,
    dtAmount DECIMAL(10,2) NOT NULL,
    dtType ENUM('deposit', 'withdrawal') NOT NULL,
    dtStatus ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    dtCreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fiUser) REFERENCES tblUser(idUser)
);

-- Bets table
CREATE TABLE tblBet (
    idBet INT AUTO_INCREMENT PRIMARY KEY,
    fiUser INT,
    fiGame INT,
    dtAmount DECIMAL(10,2) NOT NULL,
    dtOutcome ENUM('win', 'loss', 'pending') DEFAULT 'pending',
    dtWinnings DECIMAL(10,2) DEFAULT 0.00,
    dtPlacedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fiUser) REFERENCES tblUser(idUser),
    FOREIGN KEY (fiGame) REFERENCES tblGame(idGame)
);

-- Leaderboard table
CREATE TABLE tblLeaderboard (
    idRank INT AUTO_INCREMENT PRIMARY KEY,
    fiUser INT,
    dtTotalWinnings DECIMAL(10,2) DEFAULT 0.00,
    dtLastUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (fiUser) REFERENCES tblUser(idUser)
);

-- Bonus system
CREATE TABLE tblBonus (
    idBonus INT AUTO_INCREMENT PRIMARY KEY,
    fiUser INT,
    dtAmount DECIMAL(10,2) NOT NULL,
    dtStatus ENUM('active', 'expired', 'used') DEFAULT 'active',
    dtExpiresAt TIMESTAMP NULL,
    dtClaimDate datetime,
    FOREIGN KEY (fiUser) REFERENCES tblUser(idUser)
);

-- Audit log for security
CREATE TABLE tblAuditLog (
    idLog INT AUTO_INCREMENT PRIMARY KEY,
    fiUser INT,
    dtAction TEXT NOT NULL,
    dtCreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fiUser) REFERENCES tblUser(idUser)
);

-- tblMatch - Stores match/game information
CREATE TABLE tblMatch (
    idMatch VARCHAR(50) PRIMARY KEY,  -- Using API's match ID
    dtLeague VARCHAR(100) NOT NULL,   -- League name (Bundesliga, Premier League, etc.)
    dtHomeTeam VARCHAR(100) NOT NULL,
    dtAwayTeam VARCHAR(100) NOT NULL,
    dtStartTime DATETIME NOT NULL,
    dtHomeOdds DECIMAL(7,2) NULL,
    dtDrawOdds DECIMAL(7,2) NULL,
    dtAwayOdds DECIMAL(7,2) NULL,
    dtCreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dtUpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- tblBetslip - Stores bet slip information
CREATE TABLE tblBetslip (
    idBetslip INT PRIMARY KEY AUTO_INCREMENT,
    idUser INT NOT NULL,
    dtTotalOdds DECIMAL(10,2) NOT NULL,
    dtStake DECIMAL(10,2) NOT NULL,
    dtPotentialWin DECIMAL(10,2) NOT NULL,
    dtStatus ENUM('pending', 'won', 'lost', 'canceled') DEFAULT 'pending',
    dtCreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idUser) REFERENCES tblUser(idUser)
);

-- tblBetslipItem - Stores individual bets within a betslip
CREATE TABLE tblBetslipItem (
    idBetslipItem INT PRIMARY KEY AUTO_INCREMENT,
    idBetslip INT NOT NULL,
    idMatch VARCHAR(50) NOT NULL,
    dtBetType ENUM('home', 'draw', 'away') NOT NULL,
    dtOdds DECIMAL(7,2) NOT NULL,
    dtResult ENUM('pending', 'won', 'lost') DEFAULT 'pending',
    FOREIGN KEY (idBetslip) REFERENCES tblBetslip(idBetslip),
    FOREIGN KEY (idMatch) REFERENCES tblMatch(idMatch)
);-- Additional helper procedures and functions

-- Function to get user by ID
DELIMITER //
CREATE FUNCTION getUserById(user_id INT) RETURNS JSON
DETERMINISTIC
BEGIN
    DECLARE user_data JSON;
    
    SELECT JSON_OBJECT(
        'idUser', idUser,
        'dtUsername', dtUsername,
        'dtEmail', dtEmail,
        'dtBalance', dtBalance,
        'dtCreatedAt', dtCreatedAt,
        'dtLastLogin', dtLastLogin
    ) INTO user_data
    FROM tblUser
    WHERE idUser = user_id;
    
    RETURN user_data;
END //
DELIMITER ;

-- Procedure to save/update match
DELIMITER //
CREATE PROCEDURE saveMatch(
    IN p_id VARCHAR(50),
    IN p_league VARCHAR(100),
    IN p_home_team VARCHAR(100),
    IN p_away_team VARCHAR(100),
    IN p_start_time DATETIME,
    IN p_home_odds DECIMAL(7,2),
    IN p_draw_odds DECIMAL(7,2),
    IN p_away_odds DECIMAL(7,2)
)
BEGIN
    INSERT INTO tblMatch (
        idMatch, dtLeague, dtHomeTeam, dtAwayTeam, 
        dtStartTime, dtHomeOdds, dtDrawOdds, dtAwayOdds
    )
    VALUES (
        p_id, p_league, p_home_team, p_away_team,
        p_start_time, p_home_odds, p_draw_odds, p_away_odds
    )
    ON DUPLICATE KEY UPDATE
        dtLeague = p_league,
        dtHomeTeam = p_home_team,
        dtAwayTeam = p_away_team,
        dtStartTime = p_start_time,
        dtHomeOdds = p_home_odds,
        dtDrawOdds = p_draw_odds,
        dtAwayOdds = p_away_odds;
END //
DELIMITER ;

-- Procedure to update user balance
DELIMITER //
CREATE PROCEDURE updateUserBalance(
    IN p_user_id INT,
    IN p_amount DECIMAL(10,2)
)
BEGIN
    UPDATE tblUser
    SET dtBalance = dtBalance + p_amount
    WHERE idUser = p_user_id;
END //
DELIMITER ;

-- Function to save betslip and return ID
DELIMITER //
CREATE FUNCTION saveBetslip(
    p_user_id INT,
    p_total_odds DECIMAL(10,2),
    p_stake DECIMAL(10,2),
    p_potential_win DECIMAL(10,2)
) RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE betslip_id INT;
    
    INSERT INTO tblBetslip (idUser, dtTotalOdds, dtStake, dtPotentialWin)
    VALUES (p_user_id, p_total_odds, p_stake, p_potential_win);
    
    SET betslip_id = LAST_INSERT_ID();
    
    RETURN betslip_id;
END //
DELIMITER ;

-- Procedure to save betslip item
DELIMITER //
CREATE PROCEDURE saveBetslipItem(
    IN p_betslip_id INT,
    IN p_match_id VARCHAR(50),
    IN p_bet_type ENUM('home', 'draw', 'away'),
    IN p_odds DECIMAL(7,2)
)
BEGIN
    INSERT INTO tblBetslipItem (idBetslip, idMatch, dtBetType, dtOdds)
    VALUES (p_betslip_id, p_match_id, p_bet_type, p_odds);
END //
DELIMITER ;

-- Procedure to create audit log
DELIMITER //
CREATE PROCEDURE createAuditLog(
    IN p_user_id INT,
    IN p_action VARCHAR(255),
    IN p_ip VARCHAR(45)
)
BEGIN
    INSERT INTO tblAuditLog (idUser, dtAction, dtIP)
    VALUES (p_user_id, p_action, p_ip);
END //
DELIMITER ;

-- Create a view for active matches
CREATE VIEW vwActiveMatches AS
SELECT *
FROM tblMatch
WHERE dtStartTime > NOW() 
AND dtStartTime < DATE_ADD(NOW(), INTERVAL 7 DAY)
ORDER BY dtLeague, dtStartTime;

-- Create a view for user bets history
CREATE VIEW vwUserBets AS
SELECT 
    b.idBetslip,
    b.idUser,
    b.dtTotalOdds,
    b.dtStake,
    b.dtPotentialWin,
    b.dtStatus,
    b.dtCreatedAt,
    COUNT(bi.idBetslipItem) AS TotalBets,
    JSON_ARRAYAGG(
        JSON_OBJECT(
            'matchId', bi.idMatch,
            'homeTeam', m.dtHomeTeam,
            'awayTeam', m.dtAwayTeam,
            'betType', bi.dtBetType,
            'odds', bi.dtOdds,
            'result', bi.dtResult
        )
    ) AS BetDetails
FROM tblBetslip b
JOIN tblBetslipItem bi ON b.idBetslip = bi.idBetslip
JOIN tblMatch m ON bi.idMatch = m.idMatch
GROUP BY b.idBetslip;