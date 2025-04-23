# Sports Betting Website README

## Overview
This repository contains the code for a sports betting platform that allows users to view upcoming matches from various soccer leagues, place bets, and manage their betting slips and accounts. The system fetches live odds data from an external API, displays matches scheduled within the next 7 days, and provides comprehensive account management features.

## Files

### 1. get_matches.php
This file serves as an API endpoint that retrieves and returns match data.
Features:

- Fetches upcoming soccer matches from the-odds-api.com
- Supports multiple leagues (Bundesliga, Premier League, La Liga, Serie A, Ligue 1, Champions League)
- Stores match data in a database
- Implements data caching to limit API calls (updates every 4 hours)
- Returns match data in JSON format for frontend consumption

### 2. sports.php
This file contains the frontend interface for the sports betting platform.
Features:

- Responsive layout with match listings and betslip functionality
- Groups matches by league with accordion-style navigation
- Real-time odds display
- Interactive betting slip with dynamic calculations
- Bet placement with account balance integration
- Error handling and user feedback

### 3. account.php
This file provides the user account management interface.
Features:

- User profile management (username, email, password)
- Balance management with multiple deposit options
- Transaction history display
- Bonus tracking and management
- Tab-based interface for easy navigation

### 4. statistics.php
This file provides an administrative interface for viewing user statistics and balances.
Features:

- Displays a sortable and filterable table of users with their account balances
- Filters users by minimum balance amount and username
- Allows sorting by username, account balance, or registration date
- Implements secure database queries with prepared statements
- Presents data in a responsive, styled table format
- Limits results to 50 users to ensure performance
- Validates and sanitizes user input to prevent injection attacks

The interface allows administrators to quickly identify users with the highest balances and monitor account statistics. The data presentation includes:

- Username
- Email address
- Account balance (formatted in Euros)
- Registration date
- Last login timestamp

This tool integrates with the existing user management system and database structure, specifically utilizing the tblUser table described in the database structure section.

### 5. bonus-handler.php
This file serves as an API endpoint for the daily bonus reward system.
Features:

- Processes user requests for daily bonuses
- Implements a deterministic random reward system based on the current date
- Validates user sessions to ensure authentication
- Prevents multiple claims of the same bonus in a single day
- Updates user balance through a secure stored procedure
- Returns JSON responses with appropriate success or error messages
- Records bonus claims in the tblBonus table for tracking

The bonus system generates a daily reward between 100 and 1000 coins using a seeded random number generator to ensure the same reward amount for a specific day.

### 6. promotions.php
This file provides the user interface for the platform's promotional features.
Features:

- Daily bonus claim interface with an interactive button
- Session validation to ensure only logged-in users can access promotions
- Animated coin drop visual effect when bonuses are successfully claimed
- Real-time feedback messages for success or error states
- Clean, user-friendly design with consistent platform styling
- Fetch API integration with the bonus-handler endpoint
- Mobile-responsive layout

The promotions page enhances user engagement through gamification elements and provides immediate visual feedback when rewards are claimed.

### 7. casino.php
This file implements interactive casino games for the platform.
Features:

- Tab-based interface to switch between multiple games
- Slot machine game with animated spinning reels
- Roulette game with interactive wheel and betting options
- Shared credit system across games tied to user account
- Multiple betting options and strategies in roulette (odds/evens, red/black, single numbers)
- Visual win/loss effects and animations
- Customized payout multipliers based on bet type
- Real-time credit balance updates
- Ability to add credits for testing gameplay
- Responsive design with custom styling

The casino games provide engaging entertainment options beyond sports betting, with fluid animations and interactive controls. The games feature realistic mechanics such as:

- Multi-reel slot machine with symbol matching logic
- Animated roulette wheel with physics-based ball movement
- Different payout rates based on odds (2x for even/odd bets, 36x for single number bets)
- Visual feedback for game outcomes and credit changes

## Setup Requirements

- A web server with PHP support (Apache, Nginx, etc.)
- MySQL/MariaDB database
- Valid API key for the-odds-api.com (currently using key: 614d4cd73268e09019d4308930b3a2e9)
- User authentication system (session-based authentication implemented)

## Database Structure
The system requires the following database tables:

### tblMatch - Stores match information:

- idMatch - Unique match identifier
- dtLeague - League name
- dtHomeTeam - Home team name
- dtAwayTeam - Away team name
- dtStartTime - Match start time
- dtHomeOdds - Odds for home team win
- dtDrawOdds - Odds for draw
- dtAwayOdds - Odds for away team win
- dtUpdatedAt - Last update timestamp

### tblUser - Stores user account information:

- idUser - Unique user identifier
- dtUsername - Username
- dtEmail - Email address
- dtPasswordHash - Hashed password
- dtBalance - User's balance
- dtCreatedAt - Account creation date
- dtLastLogin - Last login timestamp

### tblTransaction - Stores transaction history:

- fiUser - User ID (foreign key)
- dtAmount - Transaction amount
- dtType - Transaction type (deposit/withdrawal)
- dtStatus - Transaction status (completed/pending/failed)
- dtCreatedAt - Transaction date

### tblBet - Stores betting information:

- fiUser - User ID (foreign key)
- fiGame - Game ID (foreign key)
- dtAmount - Bet amount
- dtOutcome - Bet outcome
- dtWinnings - Winnings amount
- dtPlacedAt - Bet placement date

### tblGame - Stores game information:

- idGame - Unique game identifier
- dtName - Game name
- dtType - Game type

### tblBonus - Stores user bonuses:

- fiUser - User ID (foreign key)
- dtAmount - Bonus amount
- dtStatus - Bonus status
- dtExpiresAt - Expiration date
- dtClaimDate - Date claimed

### tblAuditLog - Stores user activity logs:

- fiUser - User ID (foreign key)
- dtAction - Description of the action
- Creation timestamp (implied)

## Usage

### API Endpoint
The get_matches.php file serves as an API endpoint that returns match data in JSON format:
```json
{
  "success": true,
  "matches": [
    {
      "idMatch": "1234",
      "dtLeague": "Bundesliga",
      "dtHomeTeam": "Bayern Munich",
      "dtAwayTeam": "Borussia Dortmund",
      "dtStartTime": "2025-04-26 15:30:00",
      "dtHomeOdds": "1.75",
      "dtDrawOdds": "3.50",
      "dtAwayOdds": "4.20"
    },
    ...
  ],
  "updated": 10,
  "lastUpdate": "2025-04-23 14:30:45",
  "updateErrors": []
}
```

### Betting Interface
The sports betting interface includes:

- A loading indicator while fetching data
- Error and success message displays
- Match listings organized by league
- Interactive betting slip with real-time calculations
- Bet placement functionality (requires user login)

### Account Management
The account management interface includes:

- Profile management (update username, email, password)
- Balance management with preset and custom deposit options
- Transaction history display
- Active bonuses display
- Tab-based navigation for easy access to different sections

### Promotions and Bonuses
The promotions interface provides:

- Daily bonus claims with animated visual feedback
- Random reward amounts between 100-1000 coins
- One-time daily claim restriction
- Session-validated access control
- Immediate account balance updates

### Casino Games
The casino interface offers:

- Multiple game options accessible through tab navigation
- Slot machine with three spinning reels and symbol matching logic
- Roulette with interactive wheel animation and ball physics
- Various betting options including color, number range, and specific number bets
- Adjustable bet amounts based on user preference
- Real-time credit balance tracking
- Win/loss animations and visual feedback
- Automated payout calculations based on bet type and outcome

## Implementation Details

### API Integration
The system uses the-odds-api.com to fetch current match odds. The API key is included in the code and should be replaced with your own key for production use.

### Caching Mechanism
To reduce API calls, the system implements a caching mechanism that stores match data in the database and only updates it every 4 hours.

### User Authentication & Security
The system implements:

- Session-based authentication
- Password hashing for secure storage
- Transaction-based database operations for data integrity
- Input validation to prevent common attacks
- Activity logging for security monitoring

### User Experience
The interface includes several UX enhancements:

- Accordion-style league navigation
- Tab-based account management
- Visual selection of bets
- Real-time calculation of potential winnings
- Modal dialogs for confirmation messages
- Responsive design elements
- Animated visual feedback for bonus claims
- Interactive casino games with fluid animations

## Installation

1. Set up a web server with PHP and MySQL/MariaDB
2. Create the required database tables
3. Place the files in your web directory
4. Configure database connection in the db.php file (not included in the provided code)
5. Obtain your own API key from the-odds-api.com and update it in both files

## Security Notes

- The code includes an API key that should be properly secured in production
- User authentication uses proper password hashing
- Database queries use prepared statements to prevent SQL injection
- Session management should be reviewed for production use

## Dependencies

- CSS files: css/sports.css, css/account.css, css/casino.css (not included in the code snippets)
- Database connection: db.php (referenced but not included)
- Session management for user authentication

## Next Steps

- Replace the API key with your own
- Implement proper database connection handling
- Review and enhance security measures
- Add additional features like withdrawal processing, enhanced betting options
- Test the system thoroughly before production use