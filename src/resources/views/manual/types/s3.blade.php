<h4>S3</h4>

<p>
    S3 is a protocol that is supported by many cloud storage services such as AWS, Google Cloud, Azure or Backblaze. With these services, files are stored in "buckets" (or sometimes "containers"). An S3 storage disk can connect with one of these buckets.
</p>

<p>
    An S3 bucket must be configured for cross-origin resource sharing (CORS) before it can be used as a storage disk in BIIGLE. Take a look at the <a href="{{route('manual-tutorials', ['volumes', 'remote-locations'])}}#cors">remote locations</a> article for more information on CORS and how it must be configured for BIIGLE. Here is an example for S3 CORS rules in the XML format:
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

    <dt>Region</dt>
    <dd>
        <p>
            The cloud center region of the storage service. This is required only for some cloud storage services. If your service does not support regions, just enter something random.
        </p>
    </dd>

    <dt>Endpoint</dt>
    <dd>
        <p>
            The S3 storage endpoint of your cloud storage service. You should find this somewhere in the documentation of the service.
        </p>
    </dd>

    <dt>Subdomain/path endpoint</dt>
    <dd>
        <p>
            This option specifies how the bucket name is added to the endpoint URL. A subdomain endpoint will build an URL like <code>BUCKETNAME.s3.example.com</code>. A path endpoint will build an URL like <code>s3.example.com/BUCKETNAME</code>. You can leave this on the default setting if you are unsure. If the storage disk does not work, you can update the option later and try again.
        </p>
    </dd>
</dl>
