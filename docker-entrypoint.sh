#!/bin/sh

# Generate OTEL resource attributes from environment variables
OTEL_RESOURCE_ATTRIBUTES="service.name=${OTEL_SERVICE_NAME}"

if [ -n "${OTEL_SERVICE_VERSION}" ]; then
    OTEL_RESOURCE_ATTRIBUTES="${OTEL_RESOURCE_ATTRIBUTES},service.version=${OTEL_SERVICE_VERSION}"
fi

if [ -n "${OTEL_SERVICE_INSTANCE_ID}" ]; then
    OTEL_RESOURCE_ATTRIBUTES="${OTEL_RESOURCE_ATTRIBUTES},service.instance.id=${OTEL_SERVICE_INSTANCE_ID}"
fi

if [ -n "${OTEL_SERVICE_NAMESPACE}" ]; then
    OTEL_RESOURCE_ATTRIBUTES="${OTEL_RESOURCE_ATTRIBUTES},service.namespace=${OTEL_SERVICE_NAMESPACE}"
fi

# Create RoadRunner and PHP specific OTEL endpoints
OTEL_EXPORTER_OTLP_ENDPOINT_RR="${OTEL_EXPORTER_OTLP_ENDPOINT}"
OTEL_EXPORTER_OTLP_ENDPOINT="http://${OTEL_EXPORTER_OTLP_ENDPOINT}" # PHP require protocol to be specified

export OTEL_RESOURCE_ATTRIBUTES
export OTEL_EXPORTER_OTLP_ENDPOINT
export OTEL_EXPORTER_OTLP_ENDPOINT_RR

exec /usr/bin/rr serve -c /app/.rr.yaml "$@"
