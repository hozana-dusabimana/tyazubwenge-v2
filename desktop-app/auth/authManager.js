const axios = require('axios');
const { getDatabase } = require('../database/db');

class AuthManager {
  constructor() {
    this.token = null;
    this.user = null;
    this.baseURL = 'http://localhost/tyazubwenge_v2'; // Default, can be changed in settings
    this.loadStoredAuth();
  }

  loadStoredAuth() {
    try {
      const db = getDatabase();
      if (!db) {
        console.warn('Database not available, skipping stored auth load');
        return;
      }
      const setting = db.prepare('SELECT value FROM settings WHERE key = ?').get('auth_token');
      if (setting && setting.value) {
        this.token = setting.value;
      }

      const userSetting = db.prepare('SELECT value FROM settings WHERE key = ?').get('auth_user');
      if (userSetting && userSetting.value) {
        try {
          this.user = JSON.parse(userSetting.value);
        } catch (parseError) {
          console.error('Error parsing stored user:', parseError);
          this.user = null;
        }
      }

      // Load base URL if stored
      const urlSetting = db.prepare('SELECT value FROM settings WHERE key = ?').get('api_base_url');
      if (urlSetting && urlSetting.value) {
        this.baseURL = urlSetting.value;
      }
    } catch (error) {
      console.error('Error loading stored auth:', error);
      // Continue without stored auth
    }
  }

  async login(username, password, isOffline = false) {
    // Try offline login first if requested
    if (isOffline) {
      const db = getDatabase();
      if (db) {
        const tokenSetting = db.prepare('SELECT value FROM settings WHERE key = ?').get('auth_token');
        const userSetting = db.prepare('SELECT value FROM settings WHERE key = ?').get('auth_user');

        if (tokenSetting && userSetting) {
          this.token = tokenSetting.value;
          this.user = JSON.parse(userSetting.value);

          // Check if user matches
          if (this.user && this.user.username === username) {
            return {
              success: true,
              token: this.token,
              user: this.user,
              offline: true
            };
          }
        }
      }
      return {
        success: false,
        message: 'Offline login failed. Please connect to internet for first login.'
      };
    }

    // Online login
    try {
      const response = await axios.post(`${this.baseURL}/api/auth.php`, {
        username,
        password
      }, {
        timeout: 10000
      });

      if (response.data.success && response.data.token) {
        this.token = response.data.token;
        this.user = response.data.user;

        // Log token received
        console.log('[LOGIN] Token received from server:');
        console.log(`  - Token length: ${this.token.length}`);
        console.log(`  - Token preview: ${this.token.substring(0, 20)}...`);
        console.log(`  - Token ends with: ...${this.token.substring(this.token.length - 10)}`);

        // Store in database
        try {
          const db = getDatabase();
          if (db) {
            db.prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)').run('auth_token', this.token);
            db.prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)').run('auth_user', JSON.stringify(this.user));
            db.prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)').run('has_initial_data', '1');

            // Verify token was stored correctly
            const storedToken = db.prepare('SELECT value FROM settings WHERE key = ?').get('auth_token');
            if (storedToken && storedToken.value === this.token) {
              console.log('[LOGIN] Token stored and verified in local database');
            } else {
              console.error('[LOGIN] ERROR: Token mismatch after storage!');
              console.error(`  - Expected length: ${this.token.length}`);
              console.error(`  - Stored length: ${storedToken ? storedToken.value.length : 'NULL'}`);
            }
          }
        } catch (dbError) {
          console.error('Error saving auth to database:', dbError);
          // Continue even if database save fails
        }

        return {
          success: true,
          token: this.token,
          user: this.user,
          offline: false
        };
      } else {
        return {
          success: false,
          message: response.data.message || 'Login failed'
        };
      }
    } catch (error) {
      console.error('Login request error:', error);

      // If connection failed, try offline login
      if (error.code === 'ECONNREFUSED' || error.code === 'ETIMEDOUT' || !error.response) {
        return await this.login(username, password, true);
      }

      return {
        success: false,
        message: error.response?.data?.message || error.message || 'Connection failed'
      };
    }
  }

  async logout() {
    this.token = null;
    this.user = null;

    const db = getDatabase();
    db.prepare('DELETE FROM settings WHERE key IN (?, ?)').run('auth_token', 'auth_user');
  }

  isAuthenticated() {
    return !!this.token;
  }

  getUser() {
    return this.user;
  }

  getToken() {
    // If token is not in memory, try to reload from storage
    if (!this.token) {
      this.loadStoredAuth();
    }
    return this.token;
  }

  getBaseURL() {
    return this.baseURL;
  }

  setBaseURL(url) {
    this.baseURL = url;
    const db = getDatabase();
    db.prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)').run('api_base_url', url);
  }

  async verifyToken() {
    if (!this.token) {
      return false;
    }

    try {
      const response = await axios.get(`${this.baseURL}/api/auth.php`, {
        params: { token: this.token },
        timeout: 5000,
        validateStatus: (status) => status < 500 // Don't throw on 401/403
      });

      // If we get a 401/403, token is invalid
      if (response.status === 401 || response.status === 403) {
        console.warn('Token verification failed - token invalid or expired');
        return false;
      }

      return response.data.success && response.data.valid;
    } catch (error) {
      // Network errors don't mean token is invalid
      if (error.code === 'ECONNREFUSED' || error.code === 'ENOTFOUND' || error.code === 'ETIMEDOUT') {
        console.warn('Token verification failed due to network error - assuming token is valid');
        return true; // Assume token is valid if we can't reach server
      }

      // If token is invalid (401/403), return false but don't clear it here
      // Let the sync operation handle clearing
      if (error.response && (error.response.status === 401 || error.response.status === 403)) {
        console.warn('Token verification failed - token invalid or expired');
        return false;
      }

      // Other errors - assume token might be valid
      console.warn('Token verification error:', error.message);
      return true;
    }
  }
}

module.exports = AuthManager;

