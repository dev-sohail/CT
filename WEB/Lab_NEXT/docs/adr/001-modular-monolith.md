# ADR-001: Modular Monolith over Microservices

For a solo developer maintaining a platform for 20 years, true microservices are
a tax you cannot afford. One Laravel modular monolith with hard domain boundaries
under `app/Domains/*`. See the master plan for the full rationale.
