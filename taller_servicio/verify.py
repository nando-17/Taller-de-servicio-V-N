from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        page.goto("http://127.0.0.1:8000/ordenservicio/crear")
        page.screenshot(path="/tmp/screenshot.png")
        browser.close()

if __name__ == "__main__":
    run()
