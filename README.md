# ClientLocator.js

This script is used to redirect a client to a specific URL, but also at the same time, it captures client's location information and sends it to the backend for storage. The purpose of this tool is to collect visitor's location details using JavaScript and some APIs. The collected data can be used for analytics, security, or personalization purposes.

## Features

- **Client-side Data Collection**: Captures detailed browser, device, and user information, including battery status, screen resolution, user agent, and more.
- **Geolocation and IP Logging**: Fetches user's public IP address and integrates with geolocation services.
- **Customizable Redirection**: Redirects users to a specific URL after collecting and sending their data.
- **Asynchronous Handling**: Uses promises and `async/await` to handle asynchronous operations.

## Files Overview

### Frontend

1. **`index.html`**
   - Entry point for the application.
   - Executes JavaScript to collect client information and sends it to the backend.
   - Redirects the user to a specific URL based on query parameters or defaults to Google.

2. **`client-locator.js`**
   - Main script for gathering client-side information.
   - Uses browser APIs to fetch:
     - Battery information.
     - IP address (via `https://api.ipify.org`).
     - Screen details (resolution, orientation, color depth).
     - Navigator details (language, platform, user agent).
     - Device capabilities (memory, CPU threads).
   - Integrates with FingerprintJS for generating a unique visitor ID.

3. **`fingerprint.v4.js`**
   - External library used for fingerprinting devices to generate a unique identifier for the user.

### Backend

1. **`backend/store.php`**
   - Handles POST requests from the frontend.
   - Stores client information in sqlite database. (you can customize this part based on your requirements).

2. **`backend/view.php`**
   - Retrieves and displays stored client data (e.g., for admin or analytics purposes).

3. **`backend/config.php`**
   - Configuration file containing sensitive information such as admin credentials.

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/TarikSeyceri/ClientLocator.js.git
   ```

2. Install necessary backend dependencies (e.g., PHP environment, database setup (e.g. file writing permissions).

3. Configure the `config.php` file for backend access.

4. Serve the project:
   - Use a local server (e.g., XAMPP, WAMP, or Docker) for backend and frontend testing.

## Usage

### Frontend

1. Host the `index.html` file on your web server.
2. Provide a query parameter `t` in the URL to specify a redirection target:
   ```
   https://yourserver.com/?t=https://example.com
   ```
   If `t` is not provided, users will be redirected to Google.com by default.

### Backend

1. Ensure the backend is running and accessible at `backend/store.php`.
2. Verify that data is being sent and stored properly.
3. Use `backend/view.php` to review collected data.

## Privacy Notes

- **Fingerprinting Compliance**: This project is for Hobby purposes only!, do not use it in real life projects, if you do, Make sure your project is compliant with privacy regulations (e.g., GDPR) when using fingerprinting technologies.

## Dependencies

- [FingerprintJS v4.5.1](https://fingerprint.com/)
