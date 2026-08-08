import os
import traceback
import uvicorn


def run_api():
    port = int(os.getenv("API_PORT", os.getenv("PORT", "8000")))
    uvicorn.run("api.main:app", host="0.0.0.0", port=port, reload=os.getenv("API_RELOAD", "false").lower() == "true")


def run_main():
    try:
        run_api()
    except Exception as e:
        traceback.print_exc()
        print(f"Failed to start API: {e}")
        raise


if __name__ == "__main__":
    run_main()
