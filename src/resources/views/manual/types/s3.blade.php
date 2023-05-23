<h4><a name="s3"></a>S3</h4>

<p>
    S3 is a protocol that is supported by many cloud storage services such as AWS or Backblaze. With these services, files are stored in "buckets" (or sometimes "containers"). An S3 storage disk can connect with one of these buckets.
</p>

<p>
    An S3 bucket must be configured for cross-origin resource sharing (CORS) before it can be used as a storage disk in BIIGLE. Take a look at the <a href="{{route('manual-tutorials', ['volumes', 'remote-locations'])}}#cors">remote locations</a> article for more information on CORS and how it must be configured for BIIGLE. Example CORS rules in the JSON format and detailed setup instructions for AWS can be found below. Here is an example for CORS rules in the XML format:
</p>

<pre>
&lt;CORSConfiguration&gt;
  &lt;CORSRule&gt;
    &lt;AllowedOrigin&gt;{{url('/')}}&lt;/AllowedOrigin&gt;
    &lt;AllowedMethod&gt;GET&lt;/AllowedMethod&gt;
    &lt;AllowedHeader&gt;*&lt;/AllowedHeader&gt;
    &lt;MaxAgeSeconds&gt;30&lt;/MaxAgeSeconds&gt;
  &lt;/CORSRule&gt;
&lt;/CORSConfiguration&gt;
</pre>


<p>
    An S3 storage disk has the following options:
</p>

<dl>
    <dt>Bucket name</dt>
    <dd>
        <p>
            The name of the bucket in which the files are stored.
        </p>
    </dd>

    <dt>Region</dt>
    <dd>
        <p>
            The compute center region where the bucket is located. Leave this field empty if your cloud storage service does not support regions.
        </p>
    </dd>

    <dt>Endpoint</dt>
    <dd>
        <p>
            The S3 storage endpoint of your cloud storage service. This must be the full URL including the bucket name, region etc. You should find this somewhere in the documentation of the service.
        </p>
    </dd>

    <dt>Access key</dt>
    <dd>
        <p>
            The ID of the access credentials. You should configure an S3 bucket policy that allows only read access with these credentials. Sometimes you may even want to restrict the access to a subset of the files in the bucket.
        </p>
    </dd>

    <dt>Secret key</dt>
    <dd>
        <p>
            The secret key of the access credentials (like a password).
        </p>
    </dd>
</dl>

<p>
    Instructions to set up a storage disk with AWS:
</p>

<ol>
    <li>
        <p>
            Log in to the AWS Management Console.
        </p>
    </li>
    <li>
        <p>
            Create a new S3 bucket (<a href="https://docs.aws.amazon.com/AmazonS3/latest/userguide/creating-bucket.html">instructions</a>) and upload files to it (<a href="https://docs.aws.amazon.com/AmazonS3/latest/userguide/uploading-an-object-bucket.html">instructions</a>). Here we will call the bucket <code>MyBucket</code> and create it in the <code>eu-west-2</code> region. These are the values for the <strong>Bucket name</strong> and <strong>Region</strong> fields that are required to create a new S3 storage disk. The value of the <strong>Endpoint</strong> field can also be determined now. In this example, it will be <code>https://MyBucket.s3.eu-west-2.amazonaws.com</code>. Replace the bucket name and region in the endpoint URL to get the correct value for your storage disk.
        </p>
    </li>
    <li>
        <p>
            Click on the name of the newly created bucket in the bucket list. Then click on the "Permissions" tab.
        </p>
    </li>
    <li>
        <p>
            Scroll down to "Cross-origin resource sharing (CORS)" and click "Edit".
        </p>
    </li>
    <li>
        <p>
            Enter the following configuration:
        </p>
<pre>
[
   {
      "AllowedHeaders": ["*"],
      "AllowedMethods": ["GET"],
      "AllowedOrigins": ["{{url('/')}}"],
      "ExposeHeaders": []
   }
]
</pre>
        <p>
            Then click "Save changes".
        </p>
    </li>
    <li>
        <p>
            Now we want to create S3 access credentials. To increase security, we want to restrict the credentials to read access to <code>MyBucket</code> only.
        </p>
        <p>
            To start, select the IAM service and select "Policies" in the menu on the left.
        </p>
    </li>
    <li>
        <p>
            Click on the "Create policy" button at the top right.
        </p>
    </li>
    <li>
        <p>
            Click on the "JSON" button to enter the policy in the JSON format.
        </p>
    </li>
    <li>
        <p>
            Enter the following JSON policy (replace <code>MyBucket</code> with the name of your S3 bucket):
        </p>
<pre>
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Action": [
                "s3:GetObject",
                "s3:ListBucket"
            ],
            "Effect": "Allow",
            "Resource": [
                "arn:aws:s3:::MyBucket",
                "arn:aws:s3:::MyBucket/*"
            ]
        }
    ]
}
</pre>
        <p>
            This policy will allow only read access to the bucket.
        </p>
        <p>
            Now click the "Next" button.
        </p>
    </li>
    <li>
        <p>
            Choose a name for the policy (example: <code>biigle_MyBucket_s3_read_policy</code>) and click on the "Create policy" button.
        </p>
        <p>
            If you add more buckets and want to make them available as BIIGLE storage disks, you can create a new policy for each bucket.
        </p>
    </li>
    <li>
        <p>
            Now select "User groups" in the menu on the left and click the "Create group" button.
        </p>
    </li>
    <li>
        <p>
            Choose a name for the group (example: <code>biigle_read_access</code>) and attach the permission policy that was created above. If you add new policies for new buckets later, you can modify the user group and attach the new policies, too.
        </p>
        <p>
            Now click "Create group".
        </p>
    </li>
    <li>
        <p>
            Now select "Users" in the menu on the left and click "Add users".
        </p>
    </li>
    <li>
        <p>
            Choose a user name (example: <code>biigle_read_user</code>) and click "Next". The user does not need access to the Management Console.
        </p>
    </li>
    <li>
        <p>
            Select the user group created above and click "Next". Then click "Create user".
        </p>
    </li>
    <li>
        <p>
            Click on the user name of the newly created user. Then open the "Security credentials" tab.
        </p>
    </li>
    <li>
        <p>
            Scroll down to "Access keys" and click "Create access key".
        </p>
    </li>
    <li>
        <p>
            Select "Third-party service" in the list, acknowledge the warning below and click "Next".
        </p>
    </li>
    <li>
        <p>
            Choose a description (example: <code>biigle_access_key</code>) and click "Create access key".
        </p>
    </li>
    <li>
        <p>
            Copy the values of "Access key" and "Secret access key". These are the values for the <strong>Access key</strong> and <strong>Secret key</strong> fields that are required to create a new S3 storage disk.
        </p>
    </li>
</ol>

<p>
    Now you can fill all fields that are required to create a new S3 storage disk in BIIGLE.
</p>
