// server.js — Production entry point for Sunlight Host / cPanel / CloudLinux Passenger
const { createServer } = require("http");
const { parse } = require("url");
const next = require("next");

const dev = process.env.NODE_ENV === "development";
const hostname = process.env.HOSTNAME || "0.0.0.0";
const port = process.env.PORT ? (isNaN(process.env.PORT) ? process.env.PORT : parseInt(process.env.PORT, 10)) : 3000;

const app = next({ dev, hostname, port: typeof port === "number" ? port : 3000 });
const handle = app.getRequestHandler();

console.log(`[Sunlight Host] Preparing Next.js in ${dev ? "development" : "production"} mode...`);

app
  .prepare()
  .then(() => {
    const server = createServer(async (req, res) => {
      try {
        const parsedUrl = parse(req.url, true);
        await handle(req, res, parsedUrl);
      } catch (err) {
        console.error("[Sunlight Host] Error handling request:", req.url, err);
        res.statusCode = 500;
        res.end("Internal Server Error");
      }
    });

    server.once("error", (err) => {
      console.error("[Sunlight Host] Server startup error:", err);
      process.exit(1);
    });

    server.listen(port, () => {
      console.log(`[Sunlight Host] Ready! Listening on ${port} (PID: ${process.pid})`);
    });

    // Graceful shutdown
    const shutdown = (signal) => {
      console.log(`[Sunlight Host] Received ${signal}. Shutting down gracefully...`);
      server.close(() => {
        console.log("[Sunlight Host] HTTP server closed.");
        process.exit(0);
      });
    };

    process.on("SIGTERM", () => shutdown("SIGTERM"));
    process.on("SIGINT", () => shutdown("SIGINT"));
  })
  .catch((err) => {
    console.error("[Sunlight Host] Failed to prepare Next.js app:", err);
    process.exit(1);
  });
