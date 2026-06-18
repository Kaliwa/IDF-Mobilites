import { withSentryConfig } from "@sentry/nextjs";
import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  allowedDevOrigins: ["127.0.0.1", "localhost"],
};

export default withSentryConfig(nextConfig, {
  silent: true,
  sourcemaps: {
    disable: true,
  },
});
