# Amazon S3 validation on EC2

## Scope

On 2026-08-17, plugin version 0.2.1 was validated in the production Compose
topology on an EC2 development host running Moodle 5.2. The test used the EC2
instance role through the AWS SDK default credential provider chain. No static
AWS access key was placed in Moodle settings, `.env`, or the Compose file.

The validated path was:

1. Create a Moodle course and Page activity through Moodle APIs.
2. Generate a real course backup in `/var/moodlebackups`.
3. Discover it from the separately running Cron container.
4. Obtain temporary instance-role credentials from EC2 instance metadata.
5. Upload the archive below the dedicated S3 prefix and verify it by streamed
   SHA-256 read-back.
6. Download the content-addressed object into a directory outside the monitored
   source.
7. Verify the downloaded SHA-256 digest.
8. Restore it as a separate Moodle course and verify the Page marker.

## Result

- Source course ID: `2`
- Source shortname: `S3AWS-CLI`
- Backup size: `5327` bytes
- SHA-256:
  `f629ecd933c46d6967d8b6848fb1cce30fa5a3c4fb862ef6d3b77c6c7b2a67f9`
- Object key:
  `moodle-test/v1/f6/f629ecd933c46d6967d8b6848fb1cce30fa5a3c4fb862ef6d3b77c6c7b2a67f9.mbz`
- Upload and streamed verification: successful
- S3 download and local SHA-256 verification: successful
- Restored course ID: `3`
- Restored shortname: `S3AWS-CLI_1`
- Restored Page name: `Secure S3 verification page`
- Content marker: verified
- Browser verification: successful

The source course IDs `2` and `3`, the S3 object, and the downloaded archive at
`/var/moodlebackups/restored/aws-s3-course-2.mbz` are retained temporarily as
validation evidence. They must not be committed to Git or removed by automated
cleanup until the evaluation owner explicitly closes the validation record.

## IAM policy

Attach a dedicated least-privilege policy to the workload role already exposed
to the EC2 instance. Keep systems-management permissions and policies for other
applications separate. Replace `<bucket>` and `<prefix>` with the configured
Moodle destination; `<prefix>` has no trailing slash in this policy template.

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "AllowMoodleBucketListing",
      "Effect": "Allow",
      "Action": [
        "s3:ListBucket"
      ],
      "Resource": "arn:aws:s3:::<bucket>",
      "Condition": {
        "StringLike": {
          "s3:prefix": [
            "<prefix>",
            "<prefix>/*"
          ]
        }
      }
    },
    {
      "Sid": "AllowMoodleBackupObjects",
      "Effect": "Allow",
      "Action": [
        "s3:GetObject",
        "s3:PutObject",
        "s3:DeleteObject"
      ],
      "Resource": "arn:aws:s3:::<bucket>/<prefix>/*"
    }
  ]
}
```

The transfer implementation itself uses the object actions in the second
statement. The prefix-scoped `s3:ListBucket` permission was used by the
validation preflight and may be removed if operators do not require listing.

`s3:DeleteObject` is required only for cleanup after an incomplete or failed
verification; successfully verified remote objects are not deleted by the
current plugin. If the bucket requires a customer-managed KMS key, grant only
the corresponding key permissions and update its key policy separately.

## Docker network boundary

The Compose `internal` network is deliberately declared `internal: true` and
has no external gateway. A Cron container attached only to that network cannot
reach EC2 instance metadata or Amazon S3, so the AWS SDK cannot obtain or use
instance-role credentials.

The production topology therefore attaches `moodle-cron` to both networks:

- `internal`: MariaDB and shared Moodle application communication;
- `aws_access`: outbound access to EC2 instance metadata and Amazon S3.

The web container joins `internal` and the external Traefik `proxy` network.
The Cron container does not join `proxy`, and the database remains only on
`internal`. The `aws_access` network publishes no host port and provides
outbound routing only; IAM remains the authorization boundary for S3.

## Operational settings

The backup directory is a named volume mounted at `/var/moodlebackups` in both
the web and Cron containers. The stability interval was temporarily set to one
second for the test and restored to 60 seconds afterward. Recovery downloads
belong below `/var/moodlebackups/restored`, outside the monitored top-level
source set, so they are not treated as new outbound backups.
