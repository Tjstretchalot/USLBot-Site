# Responses

## Failure

Response Code 4xx.

```json
{
  success: false,
  error_type: "a rarely-changing string associated with the error, e.g. NOT_LOGGED_IN",
  error_message: "a human-readable string describing what went wrong, e.g. You must be logged in to do that."
}
```

## Success

### Login Success

```json
{
  success: true,
  data: {
    session_id: 'some long unique string'
  }
}
```
