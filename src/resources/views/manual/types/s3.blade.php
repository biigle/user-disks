<h4>S3</h4>

<p>
    S3 is a protocol that is supported by many cloud storage services such as AWS, Google Cloud, Azure or Backblaze. With these services, files are stored in "buckets" (or sometimes "containers"). An S3 storage disk can connect with one of these buckets.
</p>

<p>
    An S3 bucket must be configured for cross-origin resource sharing (CORS) before it can be used as a storage disk in BIIGLE. Take a look at the <a href="{{route('manual-tutorials', ['volumes', 'remote-locations'])}}#cors">remote locations</a> article for more information on CORS and how it must be configured for BIIGLE.
</p>

<p>
    Example CORS rules in the XML format:
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
    Example CORS rules in the JSON format:
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
