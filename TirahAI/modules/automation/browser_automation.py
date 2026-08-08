import time
import logging
import pyautogui
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options

# Setup Logging
logging.basicConfig(filename='browser_automation.log', level=logging.INFO, format='%(asctime)s - %(message)s')

class BrowserAutomation:
    def __init__(self, headless=False, proxy=None):
        self.headless = headless
        self.proxy = proxy
        self.driver = self._init_driver()

    def _init_driver(self):
        """ Initializes the WebDriver based on system configuration (cross-platform). """
        options = Options()
        if self.headless:
            options.add_argument("--headless")  # Run in headless mode (without opening browser UI)
        options.add_argument("--no-sandbox")
        options.add_argument("--disable-dev-shm-usage")

        if self.proxy:
            options.add_argument(f"--proxy-server={self.proxy}")

        logging.info("Initializing WebDriver...")
        driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=options)
        return driver

    def open_page(self, url):
        """ Open a URL in the browser. """
        try:
            logging.info(f"Opening page: {url}")
            self.driver.get(url)
            self.driver.maximize_window()  # Maximize the browser window
        except Exception as e:
            logging.error(f"Error opening page {url}: {e}")

    def wait_for_element(self, locator_type, locator_value, timeout=10):
        """ Wait for an element to be available before interacting with it. """
        try:
            if locator_type == "xpath":
                element = WebDriverWait(self.driver, timeout).until(
                    EC.presence_of_element_located((By.XPATH, locator_value))
                )
            elif locator_type == "id":
                element = WebDriverWait(self.driver, timeout).until(
                    EC.presence_of_element_located((By.ID, locator_value))
                )
            elif locator_type == "css":
                element = WebDriverWait(self.driver, timeout).until(
                    EC.presence_of_element_located((By.CSS_SELECTOR, locator_value))
                )
            elif locator_type == "name":
                element = WebDriverWait(self.driver, timeout).until(
                    EC.presence_of_element_located((By.NAME, locator_value))
                )
            else:
                raise ValueError("Unsupported locator type")
            logging.info(f"Element found: {locator_value}")
            return element
        except Exception as e:
            logging.error(f"Error waiting for element {locator_value}: {e}")
            return None

    def click_element(self, locator_type, locator_value):
        """ Click on a web element. """
        element = self.wait_for_element(locator_type, locator_value)
        if element:
            try:
                element.click()
                logging.info(f"Clicked on element: {locator_value}")
            except Exception as e:
                logging.error(f"Error clicking element {locator_value}: {e}")

    def type_text(self, locator_type, locator_value, text):
        """ Type text into a form field or input element. """
        element = self.wait_for_element(locator_type, locator_value)
        if element:
            try:
                element.send_keys(text)
                logging.info(f"Typed text into element: {locator_value}")
            except Exception as e:
                logging.error(f"Error typing text into element {locator_value}: {e}")

    def submit_form(self, locator_type, locator_value):
        """ Submit a form by submitting the form element. """
        element = self.wait_for_element(locator_type, locator_value)
        if element:
            try:
                element.submit()
                logging.info(f"Form submitted: {locator_value}")
            except Exception as e:
                logging.error(f"Error submitting form {locator_value}: {e}")

    def take_screenshot(self, filename="screenshot.png"):
        """ Take a screenshot of the current page. """
        try:
            self.driver.save_screenshot(filename)
            logging.info(f"Screenshot saved as {filename}")
        except Exception as e:
            logging.error(f"Error taking screenshot: {e}")

    def handle_cookies(self):
        """ Handle cookies (get, set, delete). """
        try:
            cookies = self.driver.get_cookies()
            logging.info(f"Cookies retrieved: {cookies}")
            # Example: Set a cookie
            self.driver.add_cookie({"name": "test_cookie", "value": "test_value"})
            logging.info("Test cookie set.")
            # Example: Delete a cookie
            self.driver.delete_cookie("test_cookie")
            logging.info("Test cookie deleted.")
        except Exception as e:
            logging.error(f"Error handling cookies: {e}")

    def navigate_browser(self):
        """ Perform basic browser navigation (back, forward, refresh). """
        try:
            self.driver.back()  # Go back
            logging.info("Navigated back.")
            time.sleep(2)
            self.driver.forward()  # Go forward
            logging.info("Navigated forward.")
            time.sleep(2)
            self.driver.refresh()  # Refresh page
            logging.info("Page refreshed.")
        except Exception as e:
            logging.error(f"Error navigating browser: {e}")

    def perform_mouse_action(self, x, y):
        """ Perform a mouse action (move to coordinates, click). """
        try:
            pyautogui.moveTo(x, y, duration=1)
            pyautogui.click()
            logging.info(f"Mouse moved to ({x}, {y}) and clicked.")
        except Exception as e:
            logging.error(f"Error performing mouse action: {e}")

    def close_browser(self):
        """ Close the browser window. """
        try:
            self.driver.quit()
            logging.info("Browser closed successfully.")
        except Exception as e:
            logging.error(f"Error closing browser: {e}")

if __name__ == "__main__":
    # Example usage
    automation = BrowserAutomation(headless=False)

    # Open a webpage
    automation.open_page("https://www.example.com")

    # Click on an element
    automation.click_element("xpath", "//button[@id='example_button']")

    # Type into a form
    automation.type_text("id", "example_input", "Test input")

    # Submit a form
    automation.submit_form("name", "example_form")

    # Take a screenshot
    automation.take_screenshot("example_screenshot.png")

    # Handle cookies
    automation.handle_cookies()

    # Navigate the browser
    automation.navigate_browser()

    # Perform mouse actions
    automation.perform_mouse_action(500, 400)

    # Close the browser
    automation.close_browser()
