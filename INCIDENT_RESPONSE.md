# Security Incident Response

## Immediate priorities

1. Protect patients and preserve evidence.
2. Assign an incident lead and record times/actions in a restricted incident log.
3. Contain affected accounts, tokens, hosts or integrations without deleting evidence.
4. Rotate exposed credentials and revoke affected sessions/tokens.
5. Determine affected data categories, patients, time window and third parties.

## Application actions

- Disable affected accounts with `is_active=false` and revoke their Sanctum tokens.
- Pause queues/integrations if they may continue disclosure; keep required evidence.
- Review bounded audit logs, web/server logs, storage access logs and provider logs.
- Preserve database and log snapshots with documented chain of custody.
- Do not copy message bodies, documents or clinical details into ordinary tickets or chat.

## Escalation and communication

Notify the practice privacy/security lead immediately for suspected unauthorized patient-data access. Obtain legal/privacy guidance on Nigerian Data Protection Act and healthcare notification duties, contractual deadlines and affected-person communication. Communications must be factual, approved and limited to confirmed information.

## Recovery

Patch the cause, test in isolation, restore from a verified clean point if required, rotate secrets, validate readiness, then return traffic gradually. Monitor authentication, data access and queues closely.

## After action

Within five working days, document root cause, timeline, data affected, response effectiveness and owned corrective actions. Add regression tests and update this runbook. Never close an incident solely because alerts stopped.
